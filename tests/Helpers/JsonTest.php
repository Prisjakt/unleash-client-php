<?php

namespace Prisjakt\Unleash\Tests\Helpers;

use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\Helpers\Json;

class JsonTest extends TestCase
{
    public function testDecodeValidJson()
    {
        $validJson = json_encode([]);

        $data = Json::decode($validJson, true);

        $this->assertEquals([], $data);
    }

    public function testDecodeHandlesScalars()
    {
        $jsonData = [
            "string" => "STRING",
            "number" => 1,
            "bool" => true,
        ];
        $validJson = json_encode($jsonData);

        $decodedDAta = Json::decode($validJson, true);

        $this->assertEquals($jsonData, $decodedDAta);
    }

    public function testDecodeThrowsWhenDecodingInvalidJson()
    {
        $invalidJson = "[";

        $this->expectException(\Exception::class);
        Json::decode($invalidJson, true);
    }

    public function testEncode()
    {
        $data = [
            "test" => 123,
        ];

        $json = Json::encode($data);

        $this->assertEquals(json_encode($data), $json);
    }

    public function testEncodeFailsWithInvalidUtf8Sequence()
    {
        // An invalid UTF-8 sequence
        $this->expectException(\Exception::class);
        $data = "\xB1\x31";
        Json::encode($data);
    }
}
