<?php
declare(strict_types=1);

namespace MediaServer\HttpMessage\Contract;

use Exception;

interface RequestHandler
{
    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function handle(Request $request): Response;
}