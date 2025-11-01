<?php
namespace App\Controller\Api;

use App\Entity\ContractTemplate;
use App\Repository\ContractTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/template')] // დაცულია JWT-ით
class ContractTemplateController extends AbstractController
{
    // GET /api/template - ადმინის ყველა შაბლონის სია
    #[Route('', name: 'api_template_index', methods: ['GET'])]
    public function index(ContractTemplateRepository $repo): JsonResponse
    {
        $templates = $repo->findBy(['createdBy' => $this->getUser()]);
        return $this->json($templates, Response::HTTP_OK, [], ['groups' => 'template:read']);
    }

    // POST /api/template - ახალი შაბლონის შექმნა
    #[Route('', name: 'api_template_new', methods: ['POST'])]
    public function new(Request $request, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $template = new ContractTemplate();
        $template->setName($data['name']);
        $template->setTitle($data['title']);
        $template->setContent($data['content']);
        $template->setCreatedBy($this->getUser());

        $em->persist($template);
        $em->flush();

        return $this->json($template, Response::HTTP_CREATED, [], ['groups' => 'template:read']);
    }

    // GET /api/template/{id} - ერთი შაბლონის წამოღება
    #[Route('/{id}', name: 'api_template_show', methods: ['GET'])]
    public function show(ContractTemplate $template): JsonResponse
    {
        if ($template->getCreatedBy() !== $this->getUser()) {
            return $this->json(['message' => 'Not authorized'], Response::HTTP_FORBIDDEN);
        }
        return $this->json($template, Response::HTTP_OK, [], ['groups' => 'template:read']);
    }

    // DELETE /api/template/{id} - შაბლონის წაშლა
    #[Route('/{id}', name: 'api_template_delete', methods: ['DELETE'])]
    public function delete(ContractTemplate $template, EntityManagerInterface $em): JsonResponse
    {
        if ($template->getCreatedBy() !== $this->getUser()) {
            return $this->json(['message' => 'Not authorized'], Response::HTTP_FORBIDDEN);
        }

        $em->remove($template);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
