<?php

class OrgTest extends RequestTestCase {
    static public $orgs = null;

    static public $fields = array(
        'org' => array(
            'basic'   => array('id', 'type', 'email', 'name', 'logo'),
            'details' => array('address', 'city', 'postal', 'province', 'country', 'phone',
                               'mobile', 'altphone', 'photo', 'socials', 'websites', 'tags',
            ),
        ),

    );

    /**
     * GET /org
     */
    public function testOrgList() {
        $response = $this->readRequest('org');

        // A. There are orgs
        $this->assertObjectHasAttribute('pagination', $response);
        $this->assertObjectHasAttribute('orgs', $response);

        self::$orgs = (array) $response->orgs;

        // B. There are several orgs, with these fields
        $this->assertArrayFields(self::$fields['org']['basic'], self::$orgs);
    }


    /**
     * GET /org?id=<id>
     *
     * @depends testOrgList
     */
    public function testOrgByID() {
        $item = current(self::$orgs);
        $this->assertTrue(!empty($item), 'No orgs received in previous "testOrgList" test');

        $params = array('id' => $item->id);
        $response = $this->readRequest('org', 'GET', $params);

        // A. There is an org
        $this->assertObjectHasAttribute('org', $response);
        $this->assertObjectHasAttribute('orgdetails', $response);

        // B. Check fields
        $this->assertObjectFields(self::$fields['org']['basic'], $response->org);
        $this->assertObjectFields(self::$fields['org']['details'], $response->orgdetails);

        // C. Compare values
        $this->assertTrue(json_encode($response->org) == json_encode($item), 'Org is not equal');
    }

    /**
     * GET /org/<id>
     *
     * @depends testOrgList
     */
    public function testOrgByIDAlt() {
        $item = current(self::$orgs);
        $this->assertTrue(!empty($item), 'No orgs received in previous "testOrgList" test');

        $response = $this->readRequest("org/$item->id");

        // A. There is an org
        $this->assertObjectHasAttribute('org', $response);
        $this->assertObjectHasAttribute('orgdetails', $response);

        // B. Check fields
        $this->assertObjectFields(self::$fields['org']['basic'], $response->org);
        $this->assertObjectFields(self::$fields['org']['details'], $response->orgdetails);

        // C. Compare values
        $this->assertTrue(json_encode($response->org) == json_encode($item), 'Org is not equal');
    }

    /**
     * GET /org/filter
     *
     * @depends testOrgList
     */
    public function testOrgFilter() {
        // TODO
    }
}
