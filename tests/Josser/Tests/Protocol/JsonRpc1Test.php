<?php

/*
 * This file is part of the Josser package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Josser\Tests;

use Josser\Tests\TestCase as JosserTestCase;
use Josser\Protocol\JsonRpc1;
use Josser\Client\Request\Request;
use Josser\Client\Request\RequestInterface;
use Josser\Client\Request\Notification;
use Josser\Client\Response\Response;
use Josser\Exception\InvalidResponseException;

use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * Test class for Josser\Protocol\JsonRpc1.
 */
class JsonRpc1Test extends JosserTestCase
{
    /**
     * @var \Josser\Protocol\JsonRpc1
     */
    protected $protocol;

    public function setUp()
    {
        $this->protocol = new JsonRpc1;
    }

    /**
     * Default encoder for JsonRpc1 protocol is JsonEncoder.
     *
     * @return void
     *
     */
    public function testIfDefaultEncoderIsJsonEncoder()
    {
        $this->assertEquals(new JsonEncoder, $this->protocol->getEncoder());
    }

    /**
     * Default decoder for JsonRpc1 protocol is JsonEncoder.
     *
     * @return void
     */
    public function testIfDefaultDecoderIsJsonEncoder()
    {
        $this->assertEquals(new JsonEncoder, $this->protocol->getDecoder());
    }

    /**
     * @return void
     */
    public function testEncoderRetrieval()
    {
        $endec = new JsonEncoder;
        $protocol = new JsonRpc1($endec);

        $this->assertSame($endec, $protocol->getEncoder());
    }

    /**
     * @return void
     */
    public function testDecoderRetrieval()
    {
        $endec = new JsonEncoder;
        $protocol = new JsonRpc1($endec);

        $this->assertSame($endec, $protocol->getDecoder());
    }

    /**
     * Test protocols' response objects factory and its RPC fault detection if invalid DTO is provided.
     *
     * @param mixed $responseDataTransferObject
     * @return void
     *
     * @dataProvider validResponseDataProvider
     */
    public function testCreatingResponseFromValidDTOs($responseDataTransferObject)
    {
        try {

            $response = $this->protocol->createResponse($responseDataTransferObject);
            $this->assertInstanceOf('Josser\Client\Response\ResponseInterface', $response);

        } catch (\Exception $e) {
            $this->assertInstanceOf('Josser\Exception\RpcFaultException', $e);
        }
    }

    /**
     * Test protocols' response objects factory if invalid DTO is provided.
     *
     * @param mixed $responseDataTransferObject
     * @return void
     *
     * @dataProvider invalidResponseDataProvider
     */
    public function testCreatingResponseFromInvalidDTOs($responseDataTransferObject)
    {
        $this->setExpectedException('Josser\Exception\InvalidResponseException');

        $this->protocol->createResponse($responseDataTransferObject);
    }

    /**
     * @param mixed $requestId
     * @param mixed $responseId
     * @param boolean $isMatch
     * @return void
     *
     * @dataProvider requestResponseMatchingDataProvider
     */
    public function testRequestResponseMatching($requestId, $responseId, $isMatch)
    {
        /* @var $requestStub \Josser\Client\Request\RequestInterface */
        $requestStub = $this->getMockBuilder('Josser\Client\Request\Request')
                            ->disableOriginalConstructor()
                            ->setMethods(array('getId'))
                            ->getMock();
        /* @var $responseStub \Josser\Client\Response\ResponseInterface */
        $requestStub->expects($this->once())
                    ->method('getId')
                    ->will($this->returnValue($requestId));

        $responseStub = $this->getMockBuilder('Josser\Client\Response\Response')
                             ->disableOriginalConstructor()
                             ->setMethods(array('getId'))
                             ->getMock();
        $responseStub->expects($this->once())
                     ->method('getId')
                     ->will($this->returnValue($responseId));


        $this->assertEquals($isMatch, $this->protocol->match($requestStub, $responseStub));
    }

    /**
     * @param \Josser\Client\Request\RequestInterface $request
     * @param boolean $isNotification
     *
     * @dataProvider requestsAndNotificationsDataProvider
     */
    public function testIsNotification(RequestInterface $request, $isNotification)
    {
        $this->assertEquals($isNotification, $this->protocol->isNotification($request));
    }

    /**
     * Test if protocol can generate unique request ids.
     */
    public function testGenerateRequestId()
    {
        $ids = array();

        // check uniqueness on trial of 1000 generations
        for ($i = 1; $i <= 1000; $i++) {
            $id = $this->protocol->generateRequestId();

            $this->assertNotNull($id);
            $this->assertNotContains($id, $ids);

            $ids[] = $id;
        }
    }

    /**
     * Fixtures
     *
     * @return array
     */
    protected function getValidResponseDTOs()
    {
        return array(
            array('result' => 'Hello JSON-RPC', 'error' => null, 'id' => 1),
            array('result' => null, 'error' => array('message' => 'Error message', 'code' => 1000), 'id' => 1), // RPC error
            array('result' => 'Hello JSON-RPC', 'error' => null, 'id' => null), // notification
            array('result' => 'Hello JSON-RPC', 'error' => null, 'id' => "h312g48t3iuhr8"),
            array('result' => 43534, 'error' => null, 'id' => 1),
            array('result' => 0.1, 'error' => null, 'id' => 1),
            array('result' => array('Hello' => 'World'), 'error' => null, 'id' => 1),
        );
    }

    /**
     * Fixtures.
     *
     * @return array
     */
    protected function getInvalidResponseDTOs()
    {
        return array(
            array('result' => 'Hello JSON-RPC', 'error' => null), // id missing
            array('result' => 'Hello JSON-RPC','id' => 1), // error missing
            array('error' => null, 'id' => 1), // result missing
            array('result' => null, 'error' => null, 'id' => 1), // result is null & error is null
            array('result' => null, 'error' => array('code' => 1000), 'id' => 1), // error message missing
            array('result' => null, 'error' => array('message' => 'Error message'), 'id' => 1), // error code missing
            array('result' => null, 'error' => array('message' => 'Error message', 'code' => "iashdausgd"), 'id' => 1), // code is not an integer
            array('result' => null, 'error' => array('message' => 'Error message', 'code' => array('error' => 'code')), 'id' => 1), // code is not an integer
            array('result' => null, 'error' => array('message' => 324234, 'code' => 1000), 'id' => 1), // error message is not a string
            array('result' => null, 'error' => array('message' => array('error' => 'message'), 'code' => 1000), 'id' => 1), // error message is not a string
            array('result' => null, 'error' => 345, 'id' => 1), // error is not an array
            array('result' => null, 'error' => "asdasr245", 'id' => 1), // error is not an array
            array('result' => null, 'error' => array("error"), 'id' => 1), // error is not an array
            array('result' => null, 'error' => array("error"), 'id' => new \stdClass), // id is not int, string or null
            4, // response id not an array
            '4dsf', // response is not an array
            new \stdClass, // response is empty array/object
            array(), // response is empty array
        );
    }

    /**
     * Test data.
     *
     * @return array
     */
    public function validResponseDataProvider()
    {
        $responses = array();
        foreach($this->getValidResponseDTOs() as $response) {
            $responses[] = array($response, true);
        }
        return $responses;
    }

    /**
     * Test data.
     *
     * @return array
     */
    public function invalidResponseDataProvider()
    {
        $responses = array();
        foreach($this->getInvalidResponseDTOs() as $response) {
            $responses[] = array($response, false);
        }
        return $responses;
    }

    public function responseDataProvider()
    {
        return array_merge($this->validResponseDataProvider(), $this->invalidResponseDataProvider());
    }

    /**
     * Fixtures
     *
     * @return array
     */
    public function requestResponseMatchingDataProvider()
    {
        return array(
            array(1, 1, true),
            array('asd', 'asd', true),
            array(1, 'asd', false),
            array('asd', 1, false),
        );
    }

    public function requestsAndNotificationsDataProvider()
    {
        return array(
            array(new Request('math.sum', array(1,2), 123324234), false),
            array(new Request('system.exit', array(), null), true),
            array(new Notification('system.exit', array(), null), true),
        );
    }
}