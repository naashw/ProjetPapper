<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Service\CallApiService;


#[Route('/recherche', name: 'recherche')]
class RechercheController extends AbstractController
{
    #[Route( name: 'byparam')]
    public function index(Request $request, CallApiService $callApiService): Response
    {
        // return $this->redirectToRoute("index");
        $response = null;
        $input = $request->query->get("denomination");
        $input = htmlspecialchars(preg_replace("/[^a-zA-Z0-9 ]+/", "", Strip_tags($input)));
        // dd($input);

      if (is_numeric($input) && strlen($input) == 9 || strlen($input) == 14 )
      {
        try {

          $response = $callApiService->getSirenData($input);
          return $this->redirectToRoute("entrepriseSearch", array("slug"=>$input ));

        } catch (\Exception $e) {

        }

      }else
      {
        try{
          $response = $callApiService->getDataEntrepriseList($input);
        }catch(Exception $e)
        {

          $response = null;
        }
      }

          return $this->render('recherche/index.html.twig', [
            'entrepriseList' => $response,
            'inputText' => $input,
        ]);

    }


    #[Route('/{slug}', name: 'byslug')]
    public function slug($slug, Request $request, CallApiService $callApiService): Response
    {
        $response = null;
      //  $input = $request->query->get("input");
        $input = htmlspecialchars(preg_replace("/[^a-zA-Z0-9 ]+/", "", Strip_tags($slug)));
        // dd($input);

      if (is_numeric($input) && strlen($input) == 9 || strlen($input) == 14 )
      {
        try {

          $response = $callApiService->getSirenData($input);
          return $this->redirectToRoute("entrepriseSearch", array("slug"=>$input ));

        } catch (\Exception $e) {

        }

      }else
      {
        try{
          $response = $callApiService->getDataEntrepriseList($input);
        }catch(Exception $e)
        {

          $response = null;
        }
      }

          return $this->render('recherche/index.html.twig', [
            'entrepriseList' => $response,
            'inputText' => $input,
        ]);

    }
}
