<?php

class TagTest extends RequestTestCase {
    static public $tags = null;
    static public $categories = null;

    static public $fields = array(
        'tag' => array(
            'basic'   => array('id', 'slug', 'name', 'description'),
            'details' => array('categories'),
        ),
        'category' => array(
            'basic'   => array('id', 'name'),
            'details' => array('tags'),
        ),


    );

    /**
     * GET /tag
     */
    public function testTagList() {
        $response = $this->readRequest('tag');

        // A. There are tags
        $this->assertObjectHasAttribute('tags', $response);

        self::$tags = (array) $response->tags;

        // B. There are several tags, with these fields
        $this->assertArrayFields(self::$fields['tag']['basic'], self::$tags);
    }

    /**
     * GET /tag?slug=<slug>
     *
     * @depends testTagList
     */
    public function testTagBySlug() {
        $item = current(self::$tags);
        $this->assertTrue(!empty($item), 'No tags received in previous "testTagList" test');

        $params = array('slug' => $item->slug);
        $response = $this->readRequest('tag', 'GET', $params);

        // A. Tag info
        $this->assertObjectHasAttribute('tag', $response);

        // B. Check fields
        $this->assertObjectFields(self::$fields['tag']['basic'], $response->tag);
        $this->assertObjectFields(self::$fields['tag']['details'], $response->tag);

        // C. Compare values
        $this->assertTrue($response->tag->slug == $item->slug, 'Tag is not equal');
        $this->assertTrue($response->tag->name == $item->name, 'Tag is not equal');
        $this->assertTrue($response->tag->description == $item->description, 'Tag is not equal');
    }

    /**
     * GET /tag/<id>
     *
     * @depends testTagList
     */
    public function testTagByID() {
        $item = current(self::$tags);
        $this->assertTrue(!empty($item), 'No tags received in previous "testTagList" test');

        $response = $this->readRequest("tag/$item->id");

        // A. Tag info
        $this->assertObjectHasAttribute('tag', $response);

        // B. Check fields
        $this->assertObjectFields(self::$fields['tag']['basic'], $response->tag);
        $this->assertObjectFields(self::$fields['tag']['details'], $response->tag);

        // C. Compare values
        $this->assertTrue($response->tag->slug == $item->slug, 'Tag is not equal');
        $this->assertTrue($response->tag->name == $item->name, 'Tag is not equal');
        $this->assertTrue($response->tag->description == $item->description, 'Tag is not equal');
    }

    /**
     * GET /category
     */
    public function testCategoryList() {
        $response = $this->readRequest('category');

        // A. There are categories
        $this->assertObjectHasAttribute('categories', $response);

        self::$categories = (array) $response->categories;

        // B. There are several categories, with these fields
        $this->assertArrayFields(self::$fields['category']['basic'], self::$categories);
    }

    /**
     * GET /category?id=<id>
     *
     * @depends testCategoryList
     */
    public function testCategoryByID() {
        $item = current(self::$categories);
        $this->assertTrue(!empty($item), 'No categories received in previous "testCategoryList" test');

        $params = array('id' => $item->id);
        $response = $this->readRequest('category', 'GET', $params);

        // A. Category info
        $this->assertObjectHasAttribute('category', $response);

        // B. Check fields
        $this->assertObjectFields(self::$fields['category']['basic'], $response->category);
        $this->assertObjectFields(self::$fields['category']['details'], $response->category);

        // C. Compare values
        $this->assertTrue($response->category->name == $item->name, 'Category is not equal');
    }

    /**
     * GET /category/<id>
     *
     * @depends testCategoryList
     */
    public function testCategoryByIDAlt() {
        $item = current(self::$categories);
        $this->assertTrue(!empty($item), 'No categories received in previous "testCategoryList" test');

        $response = $this->readRequest("category/$item->id");

        // A. Category info
        $this->assertObjectHasAttribute('category', $response);

        // B. Check fields
        $this->assertObjectFields(self::$fields['category']['basic'], $response->category);
        $this->assertObjectFields(self::$fields['category']['details'], $response->category);

        // C. Compare values
        $this->assertTrue($response->category->name == $item->name, 'Category is not equal');
    }
}