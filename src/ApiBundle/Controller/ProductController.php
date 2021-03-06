<?php

namespace ApiBundle\Controller;

use ApiBundle\Entity\Product;
use ApiBundle\Form\ProductType;
use ApiBundle\Traits\FormErrorValidator;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ProductController
 * @package ApiBundle\Controller
 * @Route("/products")
 */
class ProductController extends Controller
{
    use FormErrorValidator;

    /**
     * @param Request $request
     * @return Response
     * @Route("", methods={"GET"}, name="product_index")
     */
    public function indexAction(Request $request)
    {
        $search = $request->get('search', '');

        $productsData = $this->getDoctrine()
                             ->getRepository('ApiBundle:Product')
                             ->findAllProducts($search);

        $data = $this->get('ApiBundle\Service\Pagination\PaginationFactory')
                     ->paginate($productsData, $request, 'product_index');

        $products = $this->get('jms_serializer')
                         ->serialize(
                            $data,
                            'json',
                            SerializationContext::create()->setGroups(['prod_index'])
                         );

        return new Response($products, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * @param Product $product
     * @return object|Response
     * @Route("/{id}", methods={"GET"}, name="product_get")
     */
    public function getAction(Product $product)
    {
        $product = $this->get('jms_serializer')->serialize(
            $product,
            'json',
            SerializationContext::create()->setGroups(['prod_index', 'prod_single'])
        );

        return new Response($product, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("", methods={"POST"}, name="product_save")
     */
    public function saveAction(Request $request)
    {
        $data = $request->request->all();

        $doctrine = $this->getDoctrine()->getManager();

        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->submit($data);

        if (!$form->isValid()) {
            $erros = $this->getErros($form);
            $validation = [
                'type' => 'validation',
                'description' => 'Validação de Dados',
                'erros' => $erros
            ];

            return new JsonResponse($validation);
        }

        $doctrine->persist($product);
        $doctrine->flush();

        return new JsonResponse(['msg' => 'Produto inserido com sucesso!'], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @Route("", methods={"PUT"}, name="product_update")
     */
    public function updateAction(Request $request)
    {
        $data = $request->request->all();

        $doctrine = $this->getDoctrine();
        $manager = $doctrine->getManager();

        $product = $doctrine->getRepository('ApiBundle:Product')->find($data['id']);

        if (!$product) {
            return $this->createNotFoundException('Product Not Found!');
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->submit($data);

        if (!$form->isValid()) {
            $erros = $this->getErros($form);
            $validation = [
                'type' => 'validation',
                'description' => 'Validação de Dados',
                'erros' => $erros
            ];

            return new JsonResponse($validation);
        }

        $manager->merge($product);
        $manager->flush();

        return new JsonResponse(['msg' => 'Produto atualizado com sucesso!'], 200);
    }

    /**
     * @param Product $product
     * @return JsonResponse
     * @Route("/{id}", methods={"DELETE"}, name="product_delete")
     */
    public function deleteAction(Product $product)
    {
        $doctrine = $this->getDoctrine()->getManager();
        $doctrine->remove($product);
        $doctrine->flush();
        return new JsonResponse(['msg' => 'Produto removido com sucesso!'], 200);
    }
}
