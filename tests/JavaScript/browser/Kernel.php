<?php

namespace App\Tests\JavaScript\Browser;

use App\Localization\LocalePolicy;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
    }

    public function getProjectDir(): string
    {
        return dirname(__DIR__, 3);
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir().'/var/browser-tests/cache';
    }

    private function getConfigDir(): string
    {
        return $this->getProjectDir().'/var/browser-tests/config';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'isolated-browser-tests',
            'router' => ['utf8' => true],
            'asset_mapper' => [
                'paths' => [
                    $this->getProjectDir().'/assets/js' => 'js',
                    dirname(__DIR__) => 'tests',
                    $this->getProjectDir().'/var/browser-tests/vendor' => 'browser-vendor',
                ],
                'importmap_path' => __DIR__.'/importmap.php',
                'vendor_dir' => $this->getProjectDir().'/var/browser-tests/vendor',
                'importmap_polyfill' => false,
                'missing_import_mode' => 'strict',
            ],
        ]);
        $container->extension('twig', ['default_path' => __DIR__]);
        $container->services()->alias('browser_tests.twig', 'twig')->public();
        $container->services()->alias('browser_tests.asset_mapper', AssetMapperInterface::class)->public();
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('browser_tests', '/')->controller([self::class, 'page']);
    }

    public function page(): Response
    {
        $mapper = $this->getContainer()->get('browser_tests.asset_mapper');
        $testModules = [];
        foreach (require __DIR__.'/test-modules.php' as $logicalPath) {
            $testModules[] = $mapper->getPublicPath($logicalPath)
                ?? throw new \RuntimeException('Cannot resolve test module: '.$logicalPath);
        }

        return new Response($this->getContainer()->get('browser_tests.twig')->render('runner.html.twig', [
            'test_modules' => $testModules,
            'locale_catalogues' => array_combine(LocalePolicy::SUPPORTED, array_map(
                fn (string $locale): array => json_decode(file_get_contents($this->getProjectDir().'/translations/messages.'.$locale.'.json'), true, flags: JSON_THROW_ON_ERROR),
                LocalePolicy::SUPPORTED,
            )),
        ]));
    }
}
