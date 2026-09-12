<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IDCT\Adminata\Tests\Fixtures\Controller;

use IDCT\Adminata\Datagrid\ProxyQueryInterface;
use Symfony\Component\HttpFoundation\Response;

final class BatchOtherController
{
    /**
     * @param ProxyQueryInterface<object> $aCustomNameForTheProxyQuery
     */
    public function batchAction(ProxyQueryInterface $aCustomNameForTheProxyQuery): Response
    {
        return new Response('Other Controller');
    }
}
