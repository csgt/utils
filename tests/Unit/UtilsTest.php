<?php
namespace Csgt\Utils\Tests\Unit;

use Csgt\Utils\Tests\TestCase;
use Csgt\Utils\Utils;

class UtilsTest extends TestCase
{
    public function test_number_spellout_uses_the_singular_currency()
    {
        $this->assertSame('un QUETZAL', Utils::numberSpellout(1, 'Q', 0));
    }

    public function test_number_spellout_uses_the_plural_currency_and_decimals()
    {
        $this->assertSame(
            'ciento cincuenta QUETZALES con setenta y cinco',
            Utils::numberSpellout('150.75', 'Q')
        );
    }

    public function test_fecha_humano_a_mysql()
    {
        $this->assertSame('2023-12-25', Utils::fechaHumanoAMysql('25/12/2023'));
        $this->assertSame('2023-12-25 08:30', Utils::fechaHumanoAMysql('25/12/2023 08:30'));
    }

    public function test_fecha_humano_a_mysql_returns_sentinel_on_invalid_input()
    {
        $this->assertSame('0000-00-00 00:00', Utils::fechaHumanoAMysql('not-a-date'));
    }

    public function test_fecha_mysql_a_humano()
    {
        $this->assertSame('25/12/2023', Utils::fechaMysqlAHumano('2023-12-25'));
    }
}
