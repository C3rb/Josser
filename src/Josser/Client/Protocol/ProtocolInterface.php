<?php

/*
 * This file is part of the Josser package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Josser\Client\Protocol;

use Josser\Client\Response\ResponseInterface;
use Josser\Client\Request\RequestInterface;
use Josser\Endec\EndecInterface;

/**
 * Protocol interface for Josser client.
 *
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface ProtocolInterface
{
    /**
     * Create notification object.
     *
     * @abstract
     * @param string $method
     * @param array $params
     * @return \Josser\Client\Request\RequestInterface
     */
    function createNotification($method, array $params = null);

    /**
     * Create request object.
     *
     * @abstract
     * @param string $method
     * @param array|null $params
     * @return \Josser\Client\Request\RequestInterface
     */
    function createRequest($method, array $params = null);

    /**
     * Create response object.
     *
     * @abstract
     * @param mixed $dto
     * @return \Josser\Client\Response\ResponseInterface
     */
    function createResponse($dto);

    /**
     * @abstract
     * @return \Josser\Endec\EndecInterface
     */
    function getEndec();

    /**
     * Retrieve JSON-RPC version.
     *
     * @abstract
     * @return string
     */
    function getVersion();

    /**
     * Checks whether request matches response.
     *
     * @abstract
     * @param \Josser\Client\Request\RequestInterface $request
     * @param \Josser\Client\Response\ResponseInterface $response
     * @return boolean
     */
    function match(RequestInterface $request, ResponseInterface $response);

    /**
     * Validate $request object.
     *
     * @abstract
     * @param \Josser\Client\Request\RequestInterface $request
     * @return \Josser\Client\Request\RequestInterface
     */
    function validateRequest(RequestInterface $request);

    /**
     * Return DTO of a request.
     *
     * @abstract
     * @param \Josser\Client\Request\RequestInterface $request
     * @return mixed
     */
    function getRequestDataTransferObject(RequestInterface $request);

    /**
     * Validate response DTO.
     *
     * @abstract
     * @throws \Josser\Exception\RpcFaultException
     * @param mixed $dto
     * @return void
     */
    function validateResponseDataTransferObject($dto);
}
