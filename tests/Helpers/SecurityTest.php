<?php

use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase {
    public function testSanitizeInputHandlesNullAndWhitespace(): void {
        $this->assertSame('', sanitize_input(null));
        $this->assertSame('', sanitize_input('   '));
        $this->assertSame('test', sanitize_input('  test  '));
    }

    public function testSanitizeInputCleansArrays(): void {
        $input = [
            'name' => '  Krystian  ',
            'notes' => ['<b>ok</b>', '  '],
        ];

        $cleaned = sanitize_input($input);

        $this->assertSame('Krystian', $cleaned['name']);
        $this->assertSame('<b>ok</b>', $cleaned['notes'][0]);
        $this->assertSame('', $cleaned['notes'][1]);
    }

    public function testSanitizeInputStripsScripts(): void {
        $input = '<script>alert("x")</script><b>ok</b>';
        $cleaned = sanitize_input($input);

        $this->assertStringNotContainsString('<script>', $cleaned);
        $this->assertStringContainsString('ok', $cleaned);
    }

    public function testEscapeHelperHandlesNullAndQuotes(): void {
        $this->assertSame('', e(null));
        $this->assertSame('&quot;quote&quot;', e('"quote"'));
    }
}

