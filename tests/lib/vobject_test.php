<?php
/**
 * Copyright (c) 2013 Thomas Tanghus (thomas@tanghus.net)
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_VObject extends PHPUnit_Framework_TestCase {

	public static function setUpBeforeClass() {
		\Sabre\VObject\Component\VCard::$componentMap['VCARD']	= '\OCA\Contacts\VObject\VCard';
		\Sabre\VObject\Component\VCard::$propertyMap['CATEGORIES'] = 'OCA\Contacts\VObject\GroupProperty';

	}

	public function testCrappyVCard() {
		$carddata = file_get_contents(__DIR__ . '/../data/test3.vcf');
		$obj = \Sabre\VObject\Reader::read(
			$carddata,
			\Sabre\VObject\Reader::OPTION_IGNORE_INVALID_LINES
		);
		$this->assertInstanceOf('\OCA\Contacts\VObject\VCard', $obj);

		$obj->validate($obj::REPAIR|$obj::UPGRADE);

		$this->assertEquals('3.0', (string)$obj->VERSION);
		$this->assertEquals('Adèle Fermée', (string)$obj->FN);
		$this->assertEquals('Fermée;Adèle;;;', (string)$obj->N);
	}

	public function testGroupProperty() {
		$arr = array(
			'Home',
			'work',
			'Friends, Family',
		);

		$card = new \OCA\Contacts\VObject\VCard();
		$property = $card->add('CATEGORIES', $arr);
		$property->setParts($arr);

		// Test parsing and serializing
		$this->assertEquals('Home,work,Friends\, Family', $property->getValue());
		$this->assertEquals('CATEGORIES:Home,work,Friends\, Family' . "\r\n", $property->serialize());
		$this->assertEquals(3, count($property->getParts()));

		// Test add
		$property->addGroup('Coworkers');
		$this->assertTrue($property->hasGroup('coworkers'));
		$this->assertEquals(4, count($property->getParts()));
		$this->assertEquals('Home,work,Friends\, Family,Coworkers', $property->getValue());

		// Test remove
		$this->assertTrue($property->hasGroup('Friends, fAmIlY'));
		$property->removeGroup('Friends, fAmIlY');
		$this->assertEquals(3, count($property->getParts()));
		$parts = $property->getParts();
		$this->assertEquals('Coworkers', $parts[2]);

		// Test rename
		$property->renameGroup('work', 'Work');
		$parts = $property->getParts();
		$this->assertEquals('Work', $parts[1]);
		//$this->assertEquals(true, false);
	}
}