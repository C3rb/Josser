<?php

/*
 * This file is part of the Josser package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Josser\Protocol;

use Josser\Client\ResponseInterface;
use Josser\Client\Response;
use Josser\Client\RequestInterface;
use Josser\Client\Request;
use Josser\Endec\EndecInterface;
use Josser\Endec\BasicJsonEndec;
use Josser\Exception\InvalidArgumentException;
use Josser\Exception\InvalidResponseException;
use Josser\Exception\RpcFaultException;
use Josser\Protocol\JsonRpc;

/**
 * JSON-RPC 1.0 Protocol.
 *
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 */
class JsonRpc1 extends JsonRpc
{
    /**
     * Constructor.
     *
     * @param \Josser\Endec\EndecInterface|null $endec
     */
    public function __construct(EndecInterface $endec = null)
    {
        if(null === $endec)
        {
            $endec = new BasicJsonEndec();
        }
        parent::__construct($endec);
    }

    /**
     * Retrieve JSON-RPC version.
     *
     * @return string
     */
    public function getVersion()
    {
       return '1.0';
    }

    /**
     * Create response object.
     *
     * @param mixed $dto
     * @return \Josser\ResponseInterface
     */
    public function createResponse($dto)
    {
        $this->validateResponseDataTransferObject($dto);

        if(isset($dto['error'])) {
            throw new RpcFaultException($dto['error']['message']);
        }

        $result = $dto['result'];
        $id = $dto['id'];
        return new Response($result, $id);
    }

    /**
     * Validate and filter request method name.
     *
     * @throws \Josser\Exception\InvalidArgumentException
     * @param string $method
     * @return string
     */
    private function validateRequestMethod($method)
    {
        if(!is_string($method)) {
            $error = sprintf('Invalid method type. Remote method name must be string. %s detected.', gettype($method));
            throw new InvalidArgumentException($error);
        }
//        if(substr($method, 0, 4) == 'rpc.') {
//            $error = 'Invalid remote method. Method name cannot start with "rpc.".';
//            throw new InvalidArgumentException($error);
//        }
        return $method;
    }

    /**
     * Validate request parameters.
     *
     * @throws \Josser\Exception\InvalidArgumentException
     * @param array $params
     * @return array|object
     */
    private function validateRequestParams(array $params)
    {
        if(!$this->isIndexed($params)) {
            $error = 'Invalid parameters structure. Parameters must be hold within indexed-only array.';
            throw new InvalidArgumentException($error);
        }
    }

    /**
     * Validate and filter request id.
     *
     * @throws \Josser\Exception\InvalidArgumentException
     * @param mixed $id
     * @return void
     */
    private function validateRequestId($id)
    {
        if(!is_string($id) && !is_numeric($id)) {
            $error = sprintf('Invalid request id type. Request id must be string or numeric. Request id of %s type detected.', gettype($id));
            throw new InvalidArgumentException($error);
        }
    }

    /**
     * Validate $request object.
     *
     * @param \Josser\Client\RequestInterface $request
     * @return \Josser\Client\RequestInterface
     */
    public function validateRequest(RequestInterface $request)
    {
        $this->validateRequestMethod($request->getMethod());
        $this->validateRequestParams($request->getParams());
        $this->validateRequestId($request->getId());
        return $request;
    }

    /**
     * Return DTO of a request.
     *
     * @param \Josser\Client\RequestInterface $request
     * @return array
     */
    public function getRequestDataTransferObject(RequestInterface $request)
    {
        $this->validateRequest($request);

        $dto = array();
        $dto['method'] = $request->getMethod();
        if(null !== $request->getParams()) {
            $dto['params'] = $request->getParams();
        } else {
            $dto['params'] = array();
        }
        if(!$request->isNotification()) {
            $dto['id'] = $request->getId();
        } else {
            $dto['id'] = null; 
        }
        return $dto;
    }

    /**
     * Validate response DTO.
     *
     * @throws \Josser\Exception\RpcFaultException
     * @param mixed $dto
     * @return void
     */
    public function validateResponseDataTransferObject($dto)
    {
        if(!is_array($dto) && !($dto instanceof stdClass)) {
            $error = sprintf("Incorrect response type detected. An array or object expected. %s type detected.", gettype($dto));
            throw new InvalidResponseException($error);
        }
        $dto = (array) $dto;

        // id check
        if(!array_key_exists('id', $dto)) {
            $error = 'Response id not defined.';
            throw new InvalidResponseException($error);
        }
        $this->validateResponseDataTransferObjectId($dto['id']);

        if(!array_key_exists('result', $dto) && !array_key_exists('error', $dto)) {
            $error = 'Error object or result not found in response.';
            throw new InvalidResponseException($error);
        }
        
        // optional result check
        if(array_key_exists('result', $dto)) {
            $this->validateResponseDataTransferObjectResult($dto['result']);
            return;
        }

        // optional error check.
        $this->validateResponseDataTransferObjectError($dto['error']);
    }

    /**
     * @param mixed $result
     * @return void
     */
    private function validateResponseDataTransferObjectResult($result)
    {
        // no validation
    }

    /**
     * @throws \Josser\Exception\InvalidResponseException
     * @param mixed $id
     * @return void
     */
    private function validateResponseDataTransferObjectId($id)
    {
        if(!is_string($id) && !is_int($id) && !is_null($id)) {
            $error = sprintf('Invalid response id type. Response id must be integer, string or null. Response id of %s type detected.', gettype($id));
            throw new InvalidResponseException($error);
        }
    }

    /**
     * @throws \Josser\Exception\InvalidResponseException
     * @param mixed $rpcError
     * @return void
     */
    private function validateResponseDataTransferObjectError($rpcError)
    {
        if(!is_array($rpcError)) {
            $error = sprintf("Incorrect error object detected. An array or object expected. %s type detected.", gettype($rpcError));
            throw new InvalidResponseException($error);
        }
        $rpcError = (array) $rpcError;
        
        if(!array_key_exists('code', $rpcError)) {
            $error = 'Response error code is not defined.';
            throw new InvalidResponseException($error);
        }
        if(!is_int($rpcError['code'])) {
            $error = sprintf('Response error code must be an integer. "%s" detected.', gettype($rpcError['code']));
            throw new InvalidResponseException($error);
        }

        if(!array_key_exists('message', $rpcError)) {
            $error = 'Response error message is not defined.';
            throw new InvalidResponseException($error);
        }
        if(!is_string($rpcError['message'])) {
            $error = sprintf('Response error message must be a string. "%s" detected.', gettype($rpcError['message']));
            throw new InvalidResponseException($error);
        }

        // TODO: validate optional 'data' attribute
    }
}
