<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/User.php';

class UserTest extends TestCase {
    public function testVerifyCredentialsReturnsUserOnMatch(): void {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findByEmail'])
            ->getMock();

        $password = 'Testowehaslo';
        $user->expects($this->once())
            ->method('findByEmail')
            ->with('test@kracz0.pl')
            ->willReturn([
                'email' => 'test@kracz0.pl',
                'password' => password_hash($password, PASSWORD_DEFAULT),
            ]);

        $result = $user->verifyCredentials('test@kracz0.pl', $password);

        $this->assertIsArray($result);
        $this->assertSame('test@kracz0.pl', $result['email']);
    }

    public function testVerifyCredentialsReturnsNullForMissingUser(): void {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findByEmail'])
            ->getMock();

        $user->expects($this->once())
            ->method('findByEmail')
            ->with('missing@kracz0.pl')
            ->willReturn(null);

        $result = $user->verifyCredentials('missing@kracz0.pl', 'Testowehaslo123');

        $this->assertNull($result);
    }

    public function testVerifyCredentialsReturnsNullForInvalidPassword(): void {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findByEmail'])
            ->getMock();

        $user->expects($this->once())
            ->method('findByEmail')
            ->with('test@kracz0.pl')
            ->willReturn([
                'email' => 'test@kracz0.pl',
                'password' => password_hash('correct', PASSWORD_DEFAULT),
            ]);

        $result = $user->verifyCredentials('test@kracz0.pl', 'Testowehaslo321');

        $this->assertNull($result);
    }
}
