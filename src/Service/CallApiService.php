<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Config\FileLocator;
use App\Service\Constant;

class CallApiService
{

  private $client;
  private $search;
  private $constant;

  public function __construct(HttpClientInterface $client, HttpClientInterface $insee_api, Constant $constant)
  {
    $this->client = $client;
    $this->inseeApi = $insee_api;
    $this->constant = $constant;
  }



  public function getInseeSirenData($slug)
  {

    $slug = str_replace(" "," ",$slug);
    try
    {
      $response = $this->inseeApi->request(
        'GET',
        'https://api.insee.fr/entreprises/sirene/V3/siret',
        [
          'query' => [
            'q' => 'denominationUniteLegale:"'.$slug.'"',
          ],
        ]
      );

      // getting the response headers waits until they arrive
      $contentType = $response->getHeaders()['content-type'][0];

      $content = $response->getContent();
      $content = json_decode($content, true);

    }catch (\Exception $e)
    {
      $content = null;
    }

      return $content;
  }

  public function getSiretData($slug): array
  {

    $response = $this->client->request(
      'GET',
      'https://entreprise.data.gouv.fr/api/sirene/v3/etablissements/'.$slug
    );

    // trying to get the response content will block the execution until
    // the full response content is received
    $content = $response->getContent();
    $content = json_decode($content, true);
    $content = $this->mdArrayToArray($content);

    return $content;
  }

  public function getSirenData($slug): array
  {

    $response = $this->client->request(
      'GET',
      'https://entreprise.data.gouv.fr/api/sirene/v3/unites_legales/'.$slug
    );

        // getting the response headers waits until they arrive
    $contentType = $response->getHeaders()['content-type'][0];

    // trying to get the response content will block the execution until
    // the full response content is received
    $content = $response->getContent();
    $content = json_decode($content, true);
    $content = $this->mdArrayToArray($content);

    return $content;
  }

    public function getAllData($search): array
  {
      $search = strip_tags($search);
      $response = [];
      $siretResponse = [];



      if (is_numeric($search) && strlen($search) == 9)
      {
        $response =       $this->getSirenData($search);
        $siretResponse =  $this->getSiretData($response["siret"]);
        $response =       $this->mergeArrayData($response, $siretResponse);

      }elseif(is_numeric($search) && strlen($search) == 14)
      {
        $response =       $this->getSiretData($search);
        $siretResponse =  $this->getSirenData($response["siren"]);
        $response =       $this->mergeArrayData($response, $siretResponse);
      }

    return $this->mdArrayToArray($response);
  }

  private function mdArrayToArray($multidimensionalArray) : array
  {
    $response = [];
    foreach($multidimensionalArray as $k=>$v)
    {
      if(!array_key_exists($k, $response))
      {
         is_array($v) ?  $response = $this->mergeArrayData($response, $this->mdArrayToArray($v)) : $response[$k] = $v;
       }
    }
    return $response;
  }


  private function mergeArrayData($array, $dataToMerge) : array
  {
    $dataToMerge = $this->mdArrayToArray($dataToMerge);

    foreach($array as $k=>$v)
    {
      if($v == null && isset($dataToMerge[$k]) )
      {
        $array[$k] = $dataToMerge[$k];
      }
    }

    foreach($dataToMerge as $k=>$v)
    {
      if(!array_key_exists($k,$array))
      {
        $array[$k] = $v;
      }
    }

    return $array;
  }

  public function getImportantInformations($array) : array
  {

    $response = [
      "Adresse"=>$array["geo_adresse"],
      "Activite principale"=>$this->constant->getNaf($array["activite_principale"]),
      "Tranche effectifs"=> $this->constant->getEffectifSalarie($array["tranche_effectifs"]),
      "Date de création"=>$array["date_creation"],
      "Formejuridique"=>$this->constant->getCategorieJuridique($array["categorie_juridique"])
      ];

      return $response;
  }

  public function getInformationJuridiqueEntreprise($array) : array
  {

    $response = [
      "SIREN"=>$array["siren"] ?? null,
      "SIRET"=>$array["siret"] ?? null,
      "Forme juridique"=>$this->constant->getCategorieJuridique($array["categorie_juridique"]) ?? null,
      "TVA intracommunautaire"=>$array["numero_tva_intra"] ?? null,
      "SIREN"=>$array["siren"] ,
      "Activite principale"=>$this->constant->getNaf($array["activite_principale"]),
      "Tranche effectifs"=> $this->constant->getEffectifSalarie($array["tranche_effectifs"]),
      "Date de création"=>$array["date_creation"],
      ];

      return $response;
  }

  public function getActiviteEntreprise($array) : array
  {


    $response = [
      "Activite principale"=>$this->constant->getNaf($array["activite_principale"]) ?? null,
      "CODE NAF"=> $array["activite_principale"] ?? null,
      "Date de création"=>$array["date_creation"],
      ];

      return $response;
  }

  public function getDataEntrepriseList($input)
  {
    $response = [];
    $inseeData = $this->getInseeSirenData($input) ?? null;
    //dd($inseeData);
    if($inseeData == null){return null;};


    foreach($inseeData["etablissements"] as $k=>$v)
    {

      $name = $v["uniteLegale"]["denominationUniteLegale"];

      $response[$name] = $response[$name]  ?? array();

      $response[$name]["trancheEffectifs"]  = $response[$name]["trancheEffectifs"] ?? $v["trancheEffectifsEtablissement"];
      $response[$name]["trancheEffectifsDescription"] = $response[$name]["trancheEffectifsDescription"] ?? $this->constant->getEffectifSalarie($v["trancheEffectifsEtablissement"]);

      $response[$name]["siren"] = $v["siren"];

      $response[$name]["categorieJuridique"] = $v["uniteLegale"]["categorieJuridiqueUniteLegale"];
      $response[$name]["categorieJuridiqueDescription"] = $this->constant->getCategorieJuridique($v["uniteLegale"]["categorieJuridiqueUniteLegale"]);

      $response[$name]["activitePrincipale"] = $v["uniteLegale"]["activitePrincipaleUniteLegale"];
      $response[$name]["activitePrincipaleDescription"] = $this->constant->getNaf($v["uniteLegale"]["activitePrincipaleUniteLegale"]);

      $response[$name]["adresseEtablissement"] = $v["adresseEtablissement"];

    }


    return $response;

  }




}
