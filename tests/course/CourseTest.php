<?php

class CourseTest extends RequestTestCase {
    static public $courses = null;
    static public $coursetypes = null;
    static public $schools = null;
    static public $centers = null;
    static public $orgs = null;
    static public $tags = null;
    static public $categories = null;

    static public $fields = array(
        'course' => array(
            'basic'   => array('id', 'courseid', 'title', 'coursetype', 'speech'),
            'details' => array('school', 'centers', 'tags', 'description', 'details',
                               'comments', 'aimedto', 'admission', 'creditsied', 'creditsects',
                               'places', 'hours', 'editions', 'directors', 'coordinators', 'teachers',
                               'companies', 'universities', 'institutions', 'duration', 'timetable', 'assistance',
                               'degree', 'enrollment', 'priceue', 'pricenonue', 'active',
            ),
        ),
        'coursetype'  => array('id', 'label', 'name', 'prefix', 'segment', 'children'),
        'school'      => array('id', 'label', 'name'),
        'center' => array(
            'basic'   => array('id', 'label', 'name'),
            'details' => array('label', 'name', 'email', 'url', 'phone', 'fax', 'address',
                               'city', 'province', 'postal', 'country',
            ),
        ),
    );

    /**
     * GET /course
     */
    public function testCourseList() {
        $response = $this->readRequest('course');

        // A. There are courses
        $this->assertObjectHasAttribute('courses', $response);

        // $this->courses = $response->courses;
        self::$courses = (array) $response->courses;

        // B. There are several courses, with these fields
        $this->assertArrayFields(self::$fields['course']['basic'], self::$courses);
    }


    /**
     * GET /course?courseid=<courseid>
     *
     * @depends testCourseList
     */
    public function testCourseByCourseID() {
        $item = current(self::$courses);
        $this->assertTrue(!empty($item), 'No courses received in previous "testCourseList" test');

        $params = array('courseid' => $item->courseid);
        $response = $this->readRequest('course', 'GET', $params);

        // A. There are courses
        $this->assertObjectHasAttribute('courses', $response);

        $courses = (array) $response->courses;

        // B. Only one course
        $this->assertTrue(count($courses) == 1, 'Only one course could be returned');

        $course = current($courses);

        // C. Check fields
        $this->assertObjectFields(self::$fields['course']['basic'], $course);

        // D. Compare values
        $this->assertTrue(json_encode($course) == json_encode($item), 'Course is not equal');
    }

    /**
     * GET /course/<id>
     *
     * @depends testCourseList
     */
    public function testCourseDetailsByID() {
        $item = current(self::$courses);
        $this->assertTrue(!empty($item), 'No courses received in previous "testCourseList" test');

        $response = $this->readRequest("course/$item->id");

        // A. There are course basic and curse details
        $this->assertObjectHasAttribute('course', $response);
        $this->assertObjectHasAttribute('coursedetails', $response);

        // B. Check fields
        $this->assertObjectFields(self::$fields['course']['basic'], $response->course);
        $this->assertObjectFields(self::$fields['course']['details'], $response->coursedetails);

        // C. Compare values
        $this->assertTrue(json_encode($response->course) == json_encode($item), 'Course is not equal');
    }

    /**
     * GET /course/filter
     *
     * @depends testCourseList
     */
    public function testCourseFilter() {
        // TODO
    }

    /**
     * GET /coursetype
     */
    public function testCoursetypeList() {
        $response = $this->readRequest('coursetype');

        // A. There are courses
        $this->assertObjectHasAttribute('coursetypes', $response);

        self::$coursetypes = (array) $response->coursetypes;

        // B. There are several coursetypes, with these fields
        $this->assertArrayFields(self::$fields['coursetype'], self::$coursetypes);
    }

    /**
     * GET /coursetype?label=<label>
     *
     * @depends testCoursetypeList
     */
    public function testCoursetypeByLabel() {
        $item = current(self::$coursetypes);
        $this->assertTrue(!empty($item), 'No coursetypes received in previous "testCoursetypeList" test');

        $params = array('label' => $item->label);
        $response = $this->readRequest('coursetype', 'GET', $params);

        // A. Coursetype info
        $this->assertObjectHasAttribute('coursetype', $response);

        // B. Check fields
        $this->assertObjectFields(self::$fields['coursetype'], $response->coursetype);

        // C. Compare values
        $this->assertTrue(json_encode($response->coursetype) == json_encode($item), 'Coursetype is not equal');
    }

    /**
     * GET /coursetype/<id>
     *
     * @depends testCoursetypeList
     */
    public function testCoursetypeByID() {
        $item = current(self::$coursetypes);
        $this->assertTrue(!empty($item), 'No coursetypes received in previous "testCoursetypeList" test');

        $response = $this->readRequest("coursetype/$item->id");

        // A. Coursetype info
        $this->assertObjectHasAttribute('coursetype', $response);

        // B. Check fields
        $this->assertObjectFields(self::$fields['coursetype'], $response->coursetype);

        // C. Compare values
        $this->assertTrue(json_encode($response->coursetype) == json_encode($item), 'Coursetype is not equal');
    }

    /**
     * GET /school
     */
    public function testSchoolList() {
        $response = $this->readRequest('school');

        // A. There are courses
        $this->assertObjectHasAttribute('schools', $response);

        self::$schools = (array) $response->schools;

        // B. There are several coursetypes, with these fields
        $this->assertArrayFields(self::$fields['school'], self::$schools);
    }

    /**
     * GET /school?label=<label>
     *
     * @depends testSchoolList
     */
    public function testSchoolByLabel() {
        $item = current(self::$schools);
        $this->assertTrue(!empty($item), 'No schools received in previous "testSchoolList" test');

        $params = array('label' => $item->label);
        $response = $this->readRequest('school', 'GET', $params);

        // A. School info
        $this->assertObjectHasAttribute('school', $response);

        // B. Check fields
        $this->assertObjectFields(self::$fields['school'], $response->school);

        // C. Compare values
        $this->assertTrue(json_encode($response->school) == json_encode($item), 'School is not equal');
    }

    /**
     * GET /school/<id>
     *
     * @depends testSchoolList
     */
    public function testSchoolByID() {
        $item = current(self::$schools);
        $this->assertTrue(!empty($item), 'No schools received in previous "testSchoolList" test');

        $response = $this->readRequest("school/$item->id");

        // A. School info
        $this->assertObjectHasAttribute('school', $response);

        // B. Check fields
        $this->assertObjectFields(self::$fields['school'], $response->school);

        // C. Compare values
        $this->assertTrue(json_encode($response->school) == json_encode($item), 'School is not equal');
    }

    /**
     * GET /center
     */
    public function testCenterList() {
        $response = $this->readRequest('center');

        // A. There are centers
        $this->assertObjectHasAttribute('centers', $response);

        self::$centers = (array) $response->centers;

        // B. There are several centers, with these fields
        $this->assertArrayFields(self::$fields['center']['basic'], self::$centers);
    }

    /**
     * GET /center?label=<label>
     *
     * @depends testCenterList
     */
    public function testCenterByLabel() {
        $item = current(self::$centers);
        $this->assertTrue(!empty($item), 'No centers received in previous "testCenterList" test');

        $params = array('label' => $item->label);
        $response = $this->readRequest('center', 'GET', $params);

        // A. Center info
        $this->assertObjectHasAttribute('center', $response);

        // B. Check fields
        $this->assertObjectFields(self::$fields['center']['details'], $response->center);

        // C. Compare values
        $this->assertTrue($response->center->label == $item->label, 'Center is not equal');
        $this->assertTrue($response->center->name == $item->name, 'Center is not equal');
    }

    /**
     * GET /center/<id>
     *
     * @depends testCenterList
     */
    public function testCenterByID() {
        $item = current(self::$centers);
        $this->assertTrue(!empty($item), 'No centers received in previous "testCenterList" test');

        $response = $this->readRequest("center/$item->id");

        // A. center info
        $this->assertObjectHasAttribute('center', $response);

        // B. Check fields
        $this->assertObjectFields(self::$fields['center']['details'], $response->center);

        // C. Compare values
        $this->assertTrue($response->center->label == $item->label, 'Center is not equal');
        $this->assertTrue($response->center->name == $item->name, 'Center is not equal');
    }

}
