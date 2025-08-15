<?php

namespace phpMyFAQ\Service;

use PHPUnit\Framework\TestCase;

class GravatarTest extends TestCase
{
    public function testGetImage(): void
    {
        // Create a Gravatar object
        $gravatar = new Gravatar();

        // Test case 1: Test default parameters
        $email = 'test@example.com';
        $expectedResult1 = '<img src="https://secure.gravatar.com/avatar/55502f40dc8b7c769880b10874abc9d0" class="" alt="Gravatar">';
        $result1 = $gravatar->getImage($email);
        $this->assertEquals($expectedResult1, $result1);

        // Test case 2: Test with custom parameters
        $params = [
            'size' => 100,
            'default' => 'identicon',
            'rating' => 'pg',
            'force_default' => true,
            'class' => 'avatar-img'
        ];
        $expectedResult2 = '<img src="https://secure.gravatar.com/avatar/55502f40dc8b7c769880b10874abc9d0?default=identicon&amp;size=100&amp;rating=pg&amp;forcedefault=y" class="avatar-img" alt="Gravatar">';
        $result2 = $gravatar->getImage($email, $params);
        $this->assertEquals($expectedResult2, $result2);

        // Test case 3: Test with only class parameter
        $params = [
            'class' => 'rounded-img'
        ];
        $expectedResult3 = '<img src="https://secure.gravatar.com/avatar/55502f40dc8b7c769880b10874abc9d0" class="rounded-img" alt="Gravatar">';
        $result3 = $gravatar->getImage($email, $params);
        $this->assertEquals($expectedResult3, $result3);
    }
}
