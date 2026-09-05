<?php

declare(strict_types=1);

/*
 * This file is part of the adminata package.
 *
 * (c) IDCT Bartosz Pachołek <bartosz@idct.tech>
 *
 * Forked from the Sonata Project
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Adminata\Tests\App\Controller;

use Adminata\Tests\App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Controller\CRUDController;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The application's shape of a custom CRUD controller (appendix C §2): one custom row action and
 * one custom batch action with a confirmation step.
 *
 * @phpstan-extends CRUDController<Product>
 */
final class ProductCRUDController extends CRUDController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * The `archive` row action, reached through the route `configureRoutes` adds.
     */
    public function archiveAction(Request $request): Response
    {
        $object = $this->assertObjectExists($request, true);
        \assert($object instanceof Product);

        $this->admin->checkAccess('edit', $object);

        $object->setArchived(true);
        $this->entityManager->flush();

        $this->addFlash('sonata_flash_success', \sprintf('%s was archived.', $object->getName()));

        return $this->redirectToList();
    }

    /**
     * The `archive` batch action. `ask_confirmation` sends the selection through
     * `CRUD/batch_confirmation.html.twig` first, which is the page P3-08 rewrote.
     *
     * @phpstan-param ProxyQueryInterface<Product> $query
     */
    public function batchActionArchive(ProxyQueryInterface $query): Response
    {
        $this->admin->checkAccess('edit');

        $archived = 0;

        foreach ($query->execute() as $product) {
            \assert($product instanceof Product);

            $product->setArchived(true);
            ++$archived;
        }

        $this->entityManager->flush();

        $this->addFlash('sonata_flash_success', \sprintf('%d products were archived.', $archived));

        return $this->redirectToList();
    }
}
