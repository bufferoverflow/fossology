<?php
/*
Copyright (C) 2014, Siemens AG
Author: Steffen Weber

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
version 2 as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

namespace Fossology\Lib\Dao;

use Fossology\Lib\Db\DbManager;
use Fossology\Lib\Test\TestLiteDb;
use Mockery;

if (!function_exists('Traceback_uri'))
{
  function Traceback_uri(){
    return 'Traceback_uri_if_desired';
  }
}

class CopyRightDaoTest extends \PHPUnit_Framework_TestCase
{
  /** @var TestLiteDb */
  private $testDb;
  /** @var DbManager */
  private $dbManager;

  public function setUp()
  {
    $this->testDb = new TestLiteDb();
    $this->dbManager = $this->testDb->getDbManager();
  }
  
  public function tearDown()
  {
    $this->testDb = null;
    $this->dbManager = null;
  }

  public function testGetCopyrightHighlights()
  {
    $this->testDb->createPlainTables(array(),TRUE); //array('copyright'));
    $uploadDao = Mockery::mock('Fossology\Lib\Dao\UploadDao');
    $uploadDao->shouldReceive('getUploadEntry')->andReturn(array('pfile_fk'=>8));
    $copyrightDao = new CopyrightDao($this->dbManager,$uploadDao);
    $highlights = $copyrightDao->getCopyrightHighlights($uploadTreeId=1);
    $this->assertSame(array(), $highlights);
    
    $this->testDb->insertData(array('copyright'));
    $tmp=$copyrightDao->getCopyrightHighlights($uploadTreeId=1);
    $highlight0 = reset($tmp);
    $this->assertInstanceOf('Fossology\Lib\Data\Highlight', $highlight0);
    $this->assertEquals($expected=899, $highlight0->getEnd());    
  }

}
 