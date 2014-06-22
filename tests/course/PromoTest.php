<?php

class PromoTest extends RequestTestCase {
    static public $promos = null;

    static public $fields = array(
        'promo'       => array('id', 'name', 'url', 'image'),
    );

    /**
     * GET /promo
     */
    public function testPromoList() {
        $response = $this->readRequest('promo');

        // A. There are promos
        $this->assertObjectHasAttribute('promos', $response);

        self::$promos = (array) $response->promos;

        // B. There are several promos, with these fields
        $this->assertArrayFields(self::$fields['promo'], self::$promos);
    }

    /**
     * GET /promo?id=<id>
     *
     * @depends testPromoList
     */
    public function testPromoByID() {
        $item = current(self::$promos);
        $this->assertTrue(!empty($item), 'No promos received in previous "testPromoList" test');

        $params = array('id' => $item->id);
        $response = $this->readRequest('promo', 'GET', $params);

        // A. Promo info
        $this->assertObjectHasAttribute('promo', $response);

        // B. Check fields
        $this->assertObjectFields(self::$fields['promo'], $response->promo);

        // C. Check values
        $this->assertURLFQDN($response->promo->url, 'URL is not FQDN');
        $this->assertURLFQDN($response->promo->image, 'Image is not FQDN');
        $this->assertImage($response->promo->image, 'Image is not a valid image');

        // D. Compare values
        $this->assertTrue(json_encode($response->promo) == json_encode($item), 'Promo is not equal');
    }

    /**
     * GET /promo/<id>
     *
     * @depends testPromoList
     */
    public function testPromoByIDAlt() {
        $item = next(self::$promos);
        reset(self::$promos);
        $this->assertTrue(!empty($item), 'No promos received in previous "testPromoList" test');

        $response = $this->readRequest("promo/$item->id", 'GET');

        // A. Promo info
        $this->assertObjectHasAttribute('promo', $response);

        // B. Check fields
        $this->assertObjectFields(self::$fields['promo'], $response->promo);

        // C. Check values
        $this->assertURLFQDN($response->promo->url, 'URL is not FQDN');
        $this->assertURLFQDN($response->promo->image, 'Image is not FQDN');
        $this->assertImage($response->promo->image, 'Image is not a valid image');

        // D. Compare values
        $this->assertTrue(json_encode($response->promo) == json_encode($item), 'Promo is not equal');
    }
}