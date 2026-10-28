<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace core\local\guzzle;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;

/**
 * Middleware to bypass the site proxy for hosts in the "Proxy bypass hosts" setting.
 *
 * Guzzle's own 'no' proxy option only matches exact host names and domain suffixes. This applies
 * the same rules as {@see is_proxybypass()}, used by {@see \curl}, so that IP ranges, partial IP
 * addresses and wildcard domains bypass the proxy too.
 *
 * @package   core
 * @copyright 2026 David Pesce <david.pesce@exputo.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class proxy_bypass {
    /**
     * Initial setup for the request.
     *
     * @return callable
     */
    public static function setup(): callable {
        return static function (callable $handler): self {
            return new self($handler);
        };
    }

    /**
     * The following handler.
     *
     * @var callable(RequestInterface, array): PromiseInterface
     */
    private $nexthandler;

    /**
     * Proxy bypass constructor.
     *
     * @param callable $next The following handler
     */
    public function __construct(callable $next) {
        $this->nexthandler = $next;
    }

    /**
     * Turn the proxy off for the request if its host is in the proxy bypass list.
     *
     * @param RequestInterface $request The request interface
     * @param array $options The options from the request
     * @return PromiseInterface
     */
    public function __invoke(RequestInterface $request, array $options): PromiseInterface {
        $fn = $this->nexthandler;

        if (!empty($options[RequestOptions::PROXY]) && is_proxybypass((string) $request->getUri())) {
            // An empty proxy, rather than none, sets CURLOPT_PROXY to "". That turns the proxy off, including
            // one from the http_proxy environment variable, which $CFG->proxyfixunsafe sets to the site proxy.
            $options[RequestOptions::PROXY] = '';
        }

        return $fn($request, $options);
    }
}
