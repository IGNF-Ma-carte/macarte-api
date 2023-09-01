<?php

namespace App\Controller\Admin;

use App\Service\ConfigApi;
use App\Repository\MapRepository;
use App\Repository\UserRepository;
use App\Repository\ThemeRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminMapController extends AbstractController
{
    private $mapRepository;
    private $userRepository;
    private $themeRepository;
    
    public function __construct(MapRepository $mapRepository, UserRepository $userRepository, ThemeRepository $themeRepository)
    {
        $this->mapRepository = $mapRepository;
        $this->userRepository = $userRepository;
        $this->themeRepository = $themeRepository;
    }

    /**
     * @Route("/admin/cartes", name="admin_map_index")
     */
    public function index(Request $request): Response
    {
        $publicName = $request->get('user');
        $user = $this->userRepository->findOneBy(['publicName' => $publicName]);
        if(!$user){
            $publicName = null;
        }

        return $this->render('admin/map/index.html.twig', [
            'user' => $publicName,
        ]);
    }

    /**
     * @Route("/admin/cartes/{idView}/voir", name="admin_map_view", options={"expose"=true})
     */
    public function view(Request $request, string $idView, SerializerInterface $serializer, ConfigApi $configService): Response
    {
        $host = $request->headers->get('referer');
        $map = $this->mapRepository->findOneBy(array('idView' => $idView));
        $themes = $this->themeRepository->findBy(array(), array('id' => 'ASC'));

        return $this->render('admin/map/view.html.twig', [
            'macarteServer' => $this->getParameter('macarte_server'),
            'configApi' => json_encode($configService->getConfig($host)),
            'map' => $map,
            'mapJson' => $serializer->serialize($map, 'json', array('context' => 'edit', 'user' => $this->getUser())),
            'themes' => $serializer->serialize($themes, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['maps']]),
        ]);
    }

    /**
     * @Route("/admin/cartes/{id}/delete", name="admin_map_delete", options={"expose"=true})
     */
    public function delete($id, ManagerRegistry $doctrine): Response
    {
        $map = $this->mapRepository->find($id);

        // $doctrine->getManager()->remove($map);
        // $doctrine->getManager()->flush();

        $this->addFlash('success', "La carte a été supprimée");

        return $this->redirectToRoute('admin_map_index');
    }
}
