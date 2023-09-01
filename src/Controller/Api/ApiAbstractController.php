<?php

namespace App\Controller\Api;


use stdClass;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @OA\Parameter(
 *     name="id",
 *     in="path",
 *     description="Identifiant de la resource",
 *     required=true,
 *     @OA\Schema(type="integer")
 * )
 * @OA\Parameter(
 *     name="limit",
 *     in="query",
 *     description="Nombre maximum d'objets à retourner",
 *     required=false,
 *     @OA\Schema(type="integer", default=15)
 * )
 * @OA\Parameter(
 *     name="offset",
 *     in="query",
 *     description="Le nombre d'objets à ignorer avant de commencer à collecter l'ensemble de résultats",
 *     required=false,
 *     @OA\Schema(type="integer", default=0)
 * )
 * 
 * @OA\Response(
 *      response="Deleted",
 *      description="La ressource a été supprimée",
 *      @OA\JsonContent(
 *          @OA\Property(property="code", type="integer", example="204"),
 *          @OA\Property(property="message", type="string", example="La ressource a été supprimée"),
 *      )
 * )
 * @OA\Response(
 *      response="PartialContent",
 *      description="Contenu partiel des ressources recherchées",
 * )
 * @OA\Response(
 *      response="BadRequest",
 *      description="La requête n'est pas correcte",
 *      @OA\JsonContent(
 *          @OA\Property(property="code", type="integer", example="404"),
 *          @OA\Property(property="message", type="string", example="La requête n'est pas correcte"),
 *      )
 * )
 * @OA\Response(
 *      response="NotConnected",
 *      description="Vous devez être connecté",
 *      @OA\JsonContent(
 *          @OA\Property(property="code", type="integer", example="401"),
 *          @OA\Property(property="message", type="string", example="Vous devez être connecté"),
 *      )
 * )
 * @OA\Response(
 *      response="NotFound",
 *      description="La resource n'existe pas",
 *      @OA\JsonContent(
 *          @OA\Property(property="code", type="integer", example="404"),
 *          @OA\Property(property="message", type="string", example="La resource n'existe pas"),
 *      )
 * )
 * @OA\Response(
 *      response="Forbidden",
 *      description="Vous n'avez pas accès à cette ressource",
 *     @OA\JsonContent(
 *          @OA\Property(property="code", type="integer", example="403"),
 *          @OA\Property(property="message", type="string", example="Vous n'avez pas accès à cette ressource"),
 *      )
 * )
 * @OA\Response(
 *      response="Invalid",
 *      description="Vous ne pouvez pas accéder à la ressource pour des raisons légales",
 *      @OA\JsonContent(
 *          @OA\Property(property="code", type="integer", example="451"),
 *          @OA\Property(property="message", type="string", example="Vous ne pouvez pas accéder à la ressource pour des raisons légales"),
 *      )
 * )
 * @OA\Response(
 *      response="TooLarge",
 *      description="L'objet joint est trop volumineux",
 *      @OA\JsonContent(
 *          @OA\Property(property="code", type="integer", example="413"),
 *          @OA\Property(property="message", type="string", example="L'objet joint est trop volumineux"),
 *      )
 * )

 * 
 * @OA\SecurityScheme(
 *      type="apiKey",
 *      securityScheme="bearer",
 *      bearerFormat="JWT",
 *      in="header",
 *      name="Authorization",
 *      scheme="Bearer",
 * )
 */
class ApiAbstractController extends AbstractController
{
    /**
     * Crée une reponse avec un json, à utiliser principalement pour les retours en 400
     *
     * @param String $message
     * @param Int $code
     * @return Response
     */
    protected function returnResponse(String $message, Int $code):Response
    {
        $responseObject = new stdClass();
        $responseObject->code = $code;
        $responseObject->message = $message;

        return new JsonResponse($responseObject, $code);
    }
}