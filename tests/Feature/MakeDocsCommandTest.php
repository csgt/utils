<?php
namespace Csgt\Utils\Tests\Feature;

use Csgt\Utils\Tests\TestCase;

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
        $this->artisan('make:csgtdocs', ['--php' => '8.3', '--node' => '20'])
            ->assertExitCode(0);

        foreach ($this->docs as $doc) {
            $this->assertFileExists(base_path($doc));
            $this->assertStringNotContainsString('{{', file_get_contents(base_path($doc)));
        }

        $this->assertStringContainsString('## Stack', file_get_contents(base_path('README.md')));
        $this->assertStringContainsString('Conventional Commits', file_get_contents(base_path('AGENTS.md')));
        $this->assertStringContainsString('@AGENTS.md', file_get_contents(base_path('CLAUDE.md')));
    }

    public function test_it_skips_existing_files_without_force()
    {
        file_put_contents(base_path('README.md'), 'custom readme');

        $this->artisan('make:csgtdocs', ['--php' => '8.3', '--node' => '20'])
            ->assertExitCode(0);

        $this->assertSame('custom readme', file_get_contents(base_path('README.md')));
        $this->assertFileExists(base_path('AGENTS.md'));
    }

    public function test_it_fails_when_every_file_already_exists()
    {
        $this->artisan('make:csgtdocs', ['--php' => '8.3', '--node' => '20'])
            ->assertExitCode(0);

        $this->artisan('make:csgtdocs', ['--php' => '8.3', '--node' => '20'])
            ->assertExitCode(1);
    }

    public function test_force_overwrites_existing_files()
    {
        file_put_contents(base_path('README.md'), 'custom readme');

        $this->artisan('make:csgtdocs', ['--php' => '8.3', '--node' => '20', '--force' => true])
            ->assertExitCode(0);

        $this->assertStringContainsString('## Stack', file_get_contents(base_path('README.md')));
    }
}
