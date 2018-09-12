<?php

namespace Biigle\Tests\Modules\Sync\Support\Export;

use TestCase;
use Biigle\Tests\UserTest;
use Biigle\Modules\Sync\Support\Export\UserExport;

class UserExportTest extends TestCase
{
    public function testGetContent()
    {
        $user1 = UserTest::create(['settings' => ['a' => 'b']]);
        $user2 = UserTest::create();

        $export = new UserExport([$user1->id, $user2->id]);
        $expect = [
            'id' => $user1->id,
            'firstname' => $user1->firstname,
            'lastname' => $user1->lastname,
            'password' => $user1->password,
            'email' => $user1->email,
            'settings' => ['a' => 'b'],
            'uuid' => $user1->uuid,
            'affiliation' => $user1->affiliation,
        ];

        $content = $export->getContent();

        $this->assertCount(2, $content);
        $this->assertEquals($expect, $content[0]);
    }
}
