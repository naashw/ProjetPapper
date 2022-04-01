<?php

namespace App\Controller;

use App\Service\CallApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/entreprise', name: 'entreprise')]
class EntrepriseController extends AbstractController
{
    #[Route('/', name: 'Index')]
    public function index(Request $request): response
    {

      $input = $request->query->get("input");

      return $this->redirectToRoute("entrepriseSearch", array("slug"=>$input ));
    }

    #[Route('/{slug}', name: 'Search')]
    public function EntrepriseSearch($slug, CallApiService $callApiService): Response
    {
      try {
        $data = $callApiService->getAllData($slug);

      } catch (\Exception $e) {
        return $this->redirectToRoute("index");
      }
      try {

        $importantData = $callApiService->getImportantInformations($data);
        $InformationJuridique = $callApiService->getInformationJuridiqueEntreprise($data);
        $InformationActivite = $callApiService->getActiviteEntreprise($data);

      } catch (\Exception $e) {
        return $this->redirectToRoute("index");
      }



        return $this->render('entreprise/index.html.twig', [
            'controller_name' => 'EntrepriseController',
            'data' => $data,
            'importantData' => $importantData,
            'informationJuridique' => $InformationJuridique,
            'InformationActivite' => $InformationActivite,
        ]);
    }


}
