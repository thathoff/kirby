<?php

namespace Kirby\Cms;

class ExtendedModelWithContent extends ModelWithContent
{
    public function blueprint()
    {
        return 'test';
    }

    protected function commit(string $action, array $arguments, \Closure $callback)
    {
        // nothing to commit in the test
    }

    public function contentFileName(): string
    {
        return 'test.txt';
    }

    public function permissions()
    {
        return null;
    }

    public function root(): ?string
    {
        return '/tmp';
    }
}

class BrokenModelWithContent extends ExtendedModelWithContent
{
    public function root(): ?string
    {
        return null;
    }
}

class BlueprintsModelWithContent extends ExtendedModelWithContent
{
    protected $testModel;

    public function __construct(Model $model)
    {
        $this->testModel = $model;
    }

    public function blueprint()
    {
        return new Blueprint([
            'model'  => $this->testModel,
            'name'   => 'model',
            'title'  => 'Model',
            'columns' => [
                [
                    'sections' => [
                        'pages' => [
                            'name' => 'pages',
                            'type' => 'pages',
                            'parent' => 'site',
                            'templates' => [
                                'foo',
                                'bar',
                            ]
                        ],
                        'menu' => [
                            'name' => 'menu',
                            'type' => 'pages',
                            'parent' => 'site',
                            'templates' => [
                                'home',
                                'default',
                            ]
                        ]
                    ]
                ]
            ]
        ]);
    }
}

class ModelWithContentTest extends TestCase
{
    public function modelsProvider(): array
    {
        $app = new App([
            'site' => [
                'children' => [
                    [
                        'slug'  => 'foo',
                        'files' => [
                            ['filename' => 'a.jpg'],
                            ['filename' => 'b.jpg']
                        ]
                    ]
                ],
                'files' => [
                    ['filename' => 'c.jpg']
                ]
            ],
            'users' => [
                [
                    'email' => 'test@getkirby.com'
                ]
            ]
        ]);

        return [
            [$app->site()],
            [$app->page('foo')],
            [$app->site()->files()->first()],
            [$app->user('test@getkirby.com')]
        ];
    }

    public function testContentLock()
    {
        $model = new ExtendedModelWithContent();
        $this->assertInstanceOf('Kirby\\Cms\\ContentLock', $model->lock());
    }

    public function testContentLockWithNoDirectory()
    {
        $model = new BrokenModelWithContent();
        $this->assertNull($model->lock());
    }

    /**
     * @dataProvider modelsProvider
     * @param \Kirby\Cms\Model $model
     */
    public function testBlueprints($model)
    {
        $model = new BlueprintsModelWithContent($model);
        $this->assertSame([
            [
                'name' => 'foo',
                'title' => 'Foo'
            ],
            [
                'name' => 'bar',
                'title' => 'Bar'
            ],
            [
                'name' => 'home',
                'title' => 'Home'
            ],
            [
                'name' => 'Page',
                'title' => 'Page'
            ]
        ], $model->blueprints());

        $this->assertSame([
            [
                'name' => 'home',
                'title' => 'Home'
            ],
            [
                'name' => 'Page',
                'title' => 'Page'
            ]
        ], $model->blueprints('menu'));
    }

    public function testPanelImage()
    {
        $app = new App([
            'site' => [
                'files' => [
                    ['filename' => 'test.jpg']
                ]
            ]
        ]);

        $model = $app->site()->files()->first();
        $image = $app->site()->image('test.jpg');
        $hash  = $image->mediaHash();

        // cover disabled as default
        $this->assertSame([
            'ratio' => '3/2',
            'back' => 'pattern',
            'cover' => false,
            'url' => '/media/site/' . $hash . '/test.jpg',
            'cards' => [
                'url' => 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw',
                'srcset' => '/media/site/' . $hash . '/test-352x.jpg 352w, /media/site/' . $hash . '/test-864x.jpg 864w, /media/site/' . $hash . '/test-1408x.jpg 1408w'
            ],
            'list' => [
                'url' => 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw',
                'srcset' => '/media/site/' . $hash . '/test-38x.jpg 38w, /media/site/' . $hash . '/test-76x.jpg 76w'
            ]
        ], $model->panelImage());

        // cover enabled
        $this->assertSame([
            'ratio' => '3/2',
            'back' => 'pattern',
            'cover' => true,
            'url' => '/media/site/' . $hash . '/test.jpg',
            'cards' => [
                'url' => 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw',
                'srcset' => '/media/site/' . $hash . '/test-352x.jpg 352w, /media/site/' . $hash . '/test-864x.jpg 864w, /media/site/' . $hash . '/test-1408x.jpg 1408w'
            ],
            'list' => [
                'url' => 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw',
                'srcset' => '/media/site/' . $hash . '/test-38x38.jpg 1x, /media/site/' . $hash . '/test-76x76.jpg 2x'
            ]
        ], $model->panelImage(['cover' => true]));
    }
}
