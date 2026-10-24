<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use App\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class DevelopmentApiDocsKernel extends Kernel
{
    public function getCacheDir(): string
    {
        return parent::getCacheDir().'/api_docs_test';
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);

        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', [
                'test' => true,
                'session' => ['storage_factory_id' => 'session.storage.factory.mock_file'],
            ]);
        });
    }
}
