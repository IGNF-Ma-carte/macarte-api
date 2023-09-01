<?php 

namespace App\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Génère la configuration de l'API
 *
 * @author DMoreau
 */
class ConfigApi {

    private $container;

    public function __construct(ContainerInterface $container){
        $this->container = $container;
    }

    public function getConfig($host){
        $obj = new \stdClass();
        $server = $this->container->getParameter('macarte_server');
        if( str_contains($host, 'edugeo') ){
            $server = $this->container->getParameter('edugeo_server');
        }
        $obj->server = $server;

        $obj->sitePiwik = $this->container->getParameter('piwik_id'); // 239;
        $obj->gppKey = $this->container->getParameter('gpp_key');
        $obj->edugeoKey = $this->container->getParameter('edugeo_key');
        // $obj->documentation = $server.'/aide/$CATEGORIE/$ARTICLE';
        $obj->faq = $server.'/aide/faq/$CATEGORIE/$ARTICLE';
        $obj->tuto = $server.'/aide/tuto/$ARTICLE';
        $obj->version = $server.'/aide/notes-de-version/$ARTICLE';
        $obj->viewer = $server.'/carte/$ID/$TITLE?$NOTITLE';
        $obj->editor = $server.'/edition/$TYPE/$ID';
        $obj->userProfile = $server.'/utilisateur/$NAME';

        return $obj;
    }

}