<?php

namespace WechatPayTransferBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use WechatPayTransferBundle\WechatPayTransferBundle;

class WechatPayTransferBundleTest extends TestCase
{
    public function testItExtendsBundle(): void
    {
        $bundle = new WechatPayTransferBundle();
        
        $this->assertInstanceOf(Bundle::class, $bundle);
    }
    
    public function testGetPath_returnsCorrectPath(): void
    {
        $bundle = new WechatPayTransferBundle();
        
        $path = $bundle->getPath();
        
        $this->assertStringEndsWith('src', $path);
        $this->assertTrue(is_dir($path), 'Bundle path does not exist');
    }
} 