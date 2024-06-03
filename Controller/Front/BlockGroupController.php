<?php

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TheliaBlocks\Controller\Front;

use OpenApi\Annotations as OA;
use OpenApi\Controller\Front\BaseFrontOpenApiController;
use OpenApi\Model\Api\ModelFactory;
use OpenApi\Service\OpenApiService;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Model\Lang;
use TheliaBlocks\Model\Api\BlockGroup;
use TheliaBlocks\Model\BlockGroupI18nQuery;
use TheliaBlocks\Model\BlockGroupQuery;

/**
 * @Route("/open_api/block_group", name="block_group")
 */
class BlockGroupController extends BaseFrontOpenApiController
{
    /**
     * @Route("", name="_get", methods="GET")
     *
     * @OA\Get(
     *     path="/block_group",
     *     tags={"block group"},
     *     summary="Get a block group",
     *     @OA\Parameter(
     *          name="id",
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="slug",
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="visible",
     *          in="query",
     *          @OA\Schema(
     *              type="boolean",
     *              default="true"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="locale",
     *          in="query",
     *          description="Current locale by default",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(ref="#/components/schemas/BlockGroup")
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Bad request",
     *          @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function getBlockGroup(
        Request $request,
        ModelFactory $modelFactory
    ) {
        $blockGroupQuery = BlockGroupQuery::create();

        if (null !== $id = $request->get('id')) {
            $blockGroupQuery->filterById($id);
        }

        if (null !== $slug = $request->get('slug')) {
            $blockGroupQuery->filterBySlug($slug);
        }

        if ($request->get('visible') !== null) {
            $visible = (bool) json_decode(strtolower($request->get('visible')));
            $blockGroupQuery->filterByVisible($visible);
        }

        $propelBlockGroup = $blockGroupQuery->findOne();

        if (null === $propelBlockGroup) {
            return OpenApiService::jsonResponse(null, 404);
        }

        /** @var BlockGroup $blockGroup */
        $blockGroup = $modelFactory->buildModel('BlockGroup', $propelBlockGroup, $request->get('locale'));

        if (null !== $blockGroup && empty($blockGroup->getJsonContent())) {
            $requestLocale = $request->get('locale');

            if (!in_array($requestLocale, $blockGroup->getLocales())) {
                // Copy default locale JSON content
                $defaultLocale = Lang::getDefaultLanguage()->getLocale();

                $copyLocale = $blockGroup->getLocales()[0];

                if (in_array($defaultLocale, $blockGroup->getLocales())) {
                    $copyLocale = $defaultLocale;
                }

                if (
                    null !== $copyGroup = BlockGroupI18nQuery::create()
                    ->filterById($blockGroup->getId())
                    ->filterByLocale($copyLocale)
                    ->findOne()
                ) {
                    $blockGroup->setJsonContent($copyGroup->getJsonContent());
                }
            }
        }

        return OpenApiService::jsonResponse($blockGroup);
    }

    /**
     * @Route("/list", name="_get_list", methods="GET")
     *
     * @OA\Get(
     *     path="/block_group/list",
     *     tags={"block group"},
     *     summary="Get list of block groups",
     *     @OA\Parameter(
     *          name="limit",
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="offset",
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="itemType",
     *          description="the type of an item linked to the block group",
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *         name="title",
     *         in="query",
     *         @OA\Schema(   
     *             type="string"
     *         ),
     *    ),
     *     @OA\Parameter(
     *          name="itemId",
     *          description="the id of an item linked to the block group (itemType has too be defined too)",
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="visible",
     *          in="query",
     *          @OA\Schema(
     *              type="boolean",
     *              default="true"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="locale",
     *          in="query",
     *          description="Current locale by default",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(ref="#/components/schemas/BlockGroup")
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Bad request",
     *          @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function getBlockGroups(
        Request $request,
        ModelFactory $modelFactory
    ) {
        $blockGroupQuery = BlockGroupQuery::create();

        if (null !== $limit = $request->get('limit')) {
            $blockGroupQuery->limit($limit);
        }

        if (null !== $offset = $request->get('offset')) {
            $blockGroupQuery->offset($offset);
        }

        if (null !== $title = $request->get('title')) {
            $blockGroupQuery
                ->useBlockGroupI18nQuery()
                ->filterByTitle('%' . $title . '%', Criteria::LIKE)
                ->endUse();
        }

        if (null !== $itemType = $request->get('itemType')) {
            $itemBlockGroupQuery = $blockGroupQuery->useItemBlockGroupQuery()
                ->filterByItemType($itemType);

            if (null !== $itemId = $request->get('itemId')) {
                $itemBlockGroupQuery->filterByItemId($itemId);
            }

            $itemBlockGroupQuery->endUse();
        }

        if ($request->get('visible') !== null) {
            $visible = (bool) json_decode(strtolower($request->get('visible')));
            $blockGroupQuery->filterByVisible($visible);
        }

        $order = $request->get('order');

        switch ($order) {
            case 'id':
                $blockGroupQuery->orderById(Criteria::ASC);
                break;
            case 'id_reverse':
                $blockGroupQuery->orderById(Criteria::DESC);
                break;
            default:
                $blockGroupQuery->orderById(Criteria::DESC);
        }

        $propelTheliaBlocks = $blockGroupQuery->find();

        if (empty($propelTheliaBlocks)) {
            return OpenApiService::jsonResponse([], 404);
        }

        $theliaBlocks = array_map(
            fn ($propelBlockGroup) => $modelFactory->buildModel('BlockGroup', $propelBlockGroup, $request->get('locale')),
            iterator_to_array($propelTheliaBlocks)
        );

        return OpenApiService::jsonResponse($theliaBlocks);
    }
}
