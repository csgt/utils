<?php
namespace Csgt\Utils\Tests\Feature;

use Csgt\Utils\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class MakeDocsCommandTest extends TestCase
{
    protected $docs = ['README.md', 'AGENTS.md', 'CLAUDE.md'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->cleanDocs();
    }

    protected function tearDown(): void
    {
        $this->cleanDocs();

        parent::tearDown();
    }

    protected function cleanDocs()
    {
        foreach ($this->docs as $doc) {
            @unlink(base_path($doc));
        }
    }

    public function test_it_publishes_the_three_documentation_files()
    {
        $this->assertSame(0, Artisan::call('make:csgtdocs', ['--php' => '7.3', '--node' => '14']));

        foreach ($this->docs as $doc) {
            $this->assertTrue(file_exists(base_path($doc)));
            $this->assertStringNotContainsString('{{', file_get_contents(base_path($doc)));
        }

        $this->assertStringContainsString('## Stack', file_get_contents(base_path('README.md')));
        $this->assertStringContainsString('Conventional Commits', file_get_contents(base_path('AGENTS.md')));
        $this->assertStringContainsString('@AGENTS.md', file_get_contents(base_path('CLAUDE.md')));
    }

    public function test_it_skips_existing_files_without_force()
    {
        file_put_contents(base_path('README.md'), 'custom readme');

        $this->assertSame(0, Artisan::call('make:csgtdocs', ['--php' => '7.3', '--node' => '14']));

        $this->assertSame('custom readme', file_get_contents(base_path('README.md')));
        $this->assertTrue(file_exists(base_path('AGENTS.md')));
    }

    public function test_it_fails_when_every_file_already_exists()
    {
        $this->assertSame(0, Artisan::call('make:csgtdocs', ['--php' => '7.3', '--node' => '14']));
        $this->assertSame(1, Artisan::call('make:csgtdocs', ['--php' => '7.3', '--node' => '14']));
    }

    public function test_force_overwrites_existing_files()
    {
        file_put_contents(base_path('README.md'), 'custom readme');

        $this->assertSame(0, Artisan::call('make:csgtdocs', ['--php' => '7.3', '--node' => '14', '--force' => true]));

        $this->assertStringContainsString('## Stack', file_get_contents(base_path('README.md')));
    }
}
