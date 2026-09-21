<?php
namespace PhlongTaIam\Tests;

use PHPUnit\Framework\TestCase;

final class DemoXssTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_POST['txt']);
    }

    public function testUserSuppliedTextIsHtmlEscapedInOutput(): void
    {
        $payload = '<script>alert(1)</script>';
        $_POST['txt'] = $payload;

        $previousCwd = getcwd();
        chdir(__DIR__ . '/../example');
        ob_start();
        try {
            include __DIR__ . '/../example/demo.php';
        } finally {
            $output = ob_get_clean();
            chdir($previousCwd);
        }

        $this->assertStringNotContainsString(
            $payload,
            $output,
            'Raw user input must never be echoed unescaped into the page'
        );
        $this->assertStringContainsString(
            htmlspecialchars($payload, ENT_QUOTES, 'UTF-8'),
            $output,
            'The escaped form of the input should still be shown to the user'
        );
    }
}
