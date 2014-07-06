<?php

class MiscTest extends RequestTestCase {
    static public $countries = null;

    static public $fields = array(
        'language'      => array('id', 'code', 'name'),
        'country'       => array('id', 'code', 'name'),
        'province'      => array('id', 'code', 'name', 'altname1', 'altname2'),
        'timetable'     => array('code', 'name'),
        'assistance'    => array('code', 'name'),
        'degree'        => array('code', 'name'),
        'orgtype'       => array('code', 'name'),
        'usertype'      => array('code', 'name'),
        'area'          => array('id', 'label', 'name'),
    );

    /**
     * GET /misc/lang
     */
    public function testLanguageList() {
        $response = $this->readRequest('misc/language');

        // A. There are items
        $this->assertObjectHasAttribute('languages', $response);

        // B. There are several items, with these fields
        $this->assertArrayFields(self::$fields['language'], (array) $response->languages);
    }

    /**
     * GET /misc/country
     */
    public function testCountryList() {
        $response = $this->readRequest('misc/country');

        // A. There are items
        $this->assertObjectHasAttribute('countries', $response);

        self::$countries = (array) $response->countries;

        // B. There are several items, with these fields
        $this->assertArrayFields(self::$fields['country'], self::$countries);
    }

    /**
     * GET /misc/province
     *
     * @depends testCountryList
     */
    public function testProvinceList() {
        $item = current(self::$countries);
        $this->assertTrue(!empty($item), 'No countries received in previous "testCountryList" test');

        $params = array('country' => $item->code);
        $response = $this->readRequest('misc/province', 'GET', $params);

        // A. There are items
        $this->assertObjectHasAttribute('provinces', $response);

        // B. There are several items, with these fields
        $this->assertArrayFields(self::$fields['province'], (array) $response->provinces);
    }

    /**
     * GET /misc/timetable
     */
    public function testTimetableList() {
        $response = $this->readRequest('misc/timetable');

        // A. There are items
        $this->assertObjectHasAttribute('timetables', $response);

        // B. There are several items, with these fields
        $this->assertArrayFields(self::$fields['timetable'], (array) $response->timetables);
    }

    /**
     * GET /misc/assistance
     */
    public function testAssistanceList() {
        $response = $this->readRequest('misc/assistance');

        // A. There are items
        $this->assertObjectHasAttribute('assistances', $response);

        // B. There are several items, with these fields
        $this->assertArrayFields(self::$fields['assistance'], (array) $response->assistances);
    }

    /**
     * GET /misc/degree
     */
    public function testDegreeList() {
        $response = $this->readRequest('misc/degree');

        // A. There are items
        $this->assertObjectHasAttribute('degrees', $response);

        // B. There are several items, with these fields
        $this->assertArrayFields(self::$fields['degree'], (array) $response->degrees);
    }

    /**
     * GET /misc/orgtype
     */
    public function testOrgtypeList() {
        $response = $this->readRequest('misc/orgtype');

        // A. There are items
        $this->assertObjectHasAttribute('orgtypes', $response);

        // B. There are several items, with these fields
        $this->assertArrayFields(self::$fields['orgtype'], (array) $response->orgtypes);
    }

    /**
     * GET /misc/usertype
     */
    public function testUsertypeList() {
        $response = $this->readRequest('misc/usertype');

        // A. There are items
        $this->assertObjectHasAttribute('usertypes', $response);

        // B. There are several items, with these fields
        $this->assertArrayFields(self::$fields['usertype'], (array) $response->usertypes);
    }

    /**
     * GET /misc/area
     */
    public function testAreaList() {
        $response = $this->readRequest('misc/area');

        // A. There are items
        $this->assertObjectHasAttribute('areas', $response);

        // B. There are several items, with these fields
        $this->assertArrayFields(self::$fields['area'], (array) $response->areas);
    }

}