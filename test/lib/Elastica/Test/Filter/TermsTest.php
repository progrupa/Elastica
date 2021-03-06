<?php

namespace Elastica\Test\Filter;

use Elastica\Filter\Terms;
use Elastica\Test\Base as BaseTest;

class TermsTest extends BaseTest
{

    public function testLookup()
    {
        $index = $this->_createIndex();
        $type1 = $index->getType('musicians');
        $type2 = $index->getType('bands');

        //index some test data
        $type1->addDocument(new \Elastica\Document(1, array('name' => 'robert', 'lastName' => 'plant')));
        $type1->addDocument(new \Elastica\Document(2, array('name' => 'jimmy', 'lastName' => 'page')));
        $type1->addDocument(new \Elastica\Document(3, array('name' => 'john paul', 'lastName' => 'jones')));
        $type1->addDocument(new \Elastica\Document(4, array('name' => 'john', 'lastName' => 'bonham')));
        $type1->addDocument(new \Elastica\Document(5, array('name' => 'jimi', 'lastName' => 'hendrix')));

        $type2->addDocument(new \Elastica\Document('led zeppelin', array('members' => array('plant', 'page', 'jones', 'bonham'))));
        $index->refresh();

        //use the terms lookup feature to query for some data
        $termsFilter = new Terms();
        $termsFilter->setLookup('lastName', $type2, 'led zeppelin', 'members', null);
        $query = new \Elastica\Query();
        $query->setPostFilter($termsFilter);
        $results = $index->search($query);
        $this->assertEquals($results->count(), 4, 'Terms lookup with null index');

        $termsFilter->setLookup('lastName', $type2, 'led zeppelin', 'members', $index);
        $query->setPostFilter($termsFilter);
        $results = $index->search($query);
        $this->assertEquals($results->count(), 4, 'Terms lookup with index as object');

        //Query with index given as string
        $termsFilter->setLookup('lastName', $type2, 'led zeppelin', 'members', $index->getName());
        $query->setPostFilter($termsFilter);
        $results = $index->search($query);
        $this->assertEquals($results->count(), 4, 'Terms lookup with index as string');

        //Query with array of options
        $termsFilter->setLookup('lastName', $type2, 'led zeppelin', 'members', array('index' => $index, 'cache' => false));
        $query->setPostFilter($termsFilter);
        $results = $index->search($query);
        $this->assertEquals($results->count(), 4, 'Terms lookup with options array');

        $index->delete();
    }

    public function testSetExecution()
    {
        $filter = new Terms('color', array('blue', 'green'));

        $filter->setExecution('bool');
        $this->assertEquals('bool', $filter->getParam('execution'));

        $returnValue = $filter->setExecution('bool');
        $this->assertInstanceOf('Elastica\Filter\Terms', $returnValue);
    }

    public function testSetTerms()
    {
        $field = 'color';
        $terms = array('blue', 'green');

        $filter = new Terms();
        $filter->setTerms($field, $terms);
        $expected = array('terms' => array($field => $terms));
        $this->assertEquals($expected, $filter->toArray());

        $returnValue = $filter->setTerms($field, $terms);
        $this->assertInstanceOf('Elastica\Filter\Terms', $returnValue);
    }

    public function testAddTerm()
    {
        $filter = new Terms('color', array('blue'));

        $filter->addTerm('green');
        $expected = array('terms' => array('color' => array('blue', 'green')));
        $this->assertEquals($expected, $filter->toArray());

        $returnValue = $filter->addTerm('cyan');
        $this->assertInstanceOf('Elastica\Filter\Terms', $returnValue);
    }

    public function testToArray()
    {
        $filter = new Terms('color', array());
        $expected = array('terms' => array('color' => array()));
        $this->assertEquals($expected, $filter->toArray());

        $filter = new Terms('color', array('cyan'));
        $expected = array('terms' => array('color' => array('cyan')));
        $this->assertEquals($expected, $filter->toArray());
    }

    /**
     * @expectedException \Elastica\Exception\InvalidException
     */
    public function testToArrayInvalidException()
    {
        $filter = new Terms();
        $filter->toArray();
    }
}
