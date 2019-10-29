<?php

namespace Yiisoft\NetworkUtilities\Tests;


use Yiisoft\NetworkUtilities\IpHelper;
use PHPUnit\Framework\TestCase;

class IpHelperTest extends TestCase
{
    /**
     * @dataProvider getIpVersionProvider
     */
    public function testGetIpVersion(string $value, bool $validation, ?int $expectedVersion, ?string $expectedException = null, string $message = ''): void
    {
        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }
        $version = IpHelper::getIpVersion($value, $validation);
        if ($expectedException === null) {
            $this->assertSame($expectedVersion, $version, $message);
        }
    }

    public function getIpVersionProvider(): array
    {
        return [
            'emptyString' => ['', false, null, \InvalidArgumentException::class],
            'emptyStringValidate' => ['', true, null, \InvalidArgumentException::class],
            'tooShort' => ['1', false, null, \InvalidArgumentException::class],
            'tooShortValidate' => ['1', true, null, \InvalidArgumentException::class],
            'ipv4Minimal' => ['0.0.0.0', false, IpHelper::IPV4],
            'ipv4TooShort' => ['0.0.0.', false, null, \InvalidArgumentException::class],
            'ipv4' => ['192.168.0.1', false, IpHelper::IPV4],
            'ipv4Max' => ['255.255.255.255', false, IpHelper::IPV4],
            'ipv4MaxValidation' => ['255.255.255.255', true, IpHelper::IPV4],
            'ipv4OverMax' => ['255.255.255.256', true, null, \InvalidArgumentException::class],
            'ipv4Cidr' => ['192.168.0.1/24', false, IpHelper::IPV4, null, 'IPv4 with CIDR is resolved correctly'],
            'ipv4CidrValidation' => ['192.168.0.1/24', true, null, \InvalidArgumentException::class],
            'ipv6' => ['fb01::1', false, IpHelper::IPV6],
            'ipv6Cidr' => ['fb01::1/24', false, IpHelper::IPV6, null, 'IPv6 with CIDR is resolved correctly'],
            'ipv6CidrValidation' => ['fb01::1/24', true, null, \InvalidArgumentException::class],
            'ipv6Minimal' => ['::', false, IpHelper::IPV6],
            'ipv6MinimalValidation' => ['::', true, IpHelper::IPV6],
            'ipv6MappedIpv4' => ['::ffff:192.168.0.2', false, IpHelper::IPV6],
            'ipv6MappedIpv4Validation' => ['::ffff:192.168.0.2', true, IpHelper::IPV6],
            'ipv6Full' => ['fa01:0000:0000:0000:0000:0000:0000:0001', false, IpHelper::IPV6],
            'ipv6FullValidation' => ['fa01:0000:0000:0000:0000:0000:0000:0001', true, IpHelper::IPV6],
        ];
    }

    /**
     * @dataProvider expandIpv6Provider
     */
    public function testExpandIpv6(string $value, string $expected): void
    {
        $expanded = IpHelper::expandIPv6($value);
        $this->assertSame($expected, $expanded);
    }

    public function expandIpv6Provider(): array
    {
        return [
            ['fa01::1', 'fa01:0000:0000:0000:0000:0000:0000:0001'],
            ['2001:db0:1:2::7', '2001:0db0:0001:0002:0000:0000:0000:0007'],
        ];
    }

    public function testIpv6ExpandingWithInvalidValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        IpHelper::expandIPv6('fa01::1/64');
    }

    /**
     * @dataProvider ip2binProvider
     */
    public function testIp2bin(string $value, string $expected): void
    {
        $result = IpHelper::ip2bin($value);
        $this->assertSame($expected, $result);
    }

    public function ip2binProvider(): array
    {
        return [
            ['192.168.1.1', '11000000101010000000000100000001'],
            ['fa01:0000:0000:0000:0000:0000:0000:0001', '11111010000000010000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000001'],
            ['fa01::1', '11111010000000010000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000001'],
            ['2620:0:2d0:200::7', '00100110001000000000000000000000000000101101000000000010000000000000000000000000000000000000000000000000000000000000000000000111'],
        ];
    }

    /**
     * @dataProvider inRangeProvider
     */
    public function testInRange(string $value, string $range, bool $expected): void
    {
        $result = IpHelper::inRange($value, $range);
        $this->assertSame($expected, $result);
    }

    public function inRangeProvider(): array
    {
        return [
            ['192.168.1.1/24', '192.168.0.0/23', true],
            ['192.168.1.1/24', '192.168.0.0/24', false],
            ['192.168.1.1/24', '0.0.0.0/0', true],
            ['192.168.1.1/32', '192.168.1.1', true],
            ['192.168.1.1/32', '192.168.1.1/32', true],
            ['192.168.1.1', '192.168.1.1/32', true],
            ['fa01::1/128', 'fa01::/64', true],
            ['fa01::1/128', 'fa01::1/128', true],
            ['fa01::1/64', 'fa01::1/128', false],
            ['2620:0:0:0:0:0:0:0', '2620:0:2d0:200::7/32', true],
        ];
    }
}