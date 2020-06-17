<?php

namespace Application\Service;

use Zend\Http\PhpEnvironment\Request as Request;
use Zend\Router\Http\TreeRouteStack;
use Zend\View\HelperPluginManager;

class ComeBackUrlCreator
{
    const PARAM_NAME = 'return_url';

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var TreeRouteStack
     */
    protected $router;

    /**
     * @var HelperPluginManager
     */
    protected $helperPluginManager;

    /**
     * ComeBackUrlCreator constructor.
     *
     * @param Request $request
     * @param TreeRouteStack $router
     */
    public function __construct(Request $request, TreeRouteStack $router, HelperPluginManager $helperPluginManager)
    {
        $this->request = $request;
        $this->router = $router;
        $this->helperPluginManager = $helperPluginManager;
    }

    /**
     * @return string
     */
    public function getComeBackUrl()
    {
        $params = $this->request->getQuery()->toArray();
        $redirectUrl = '';

        if (!empty($params[self::PARAM_NAME])) {
            $redirectUrl = urldecode($params[self::PARAM_NAME]);
        }

        $position = stripos($redirectUrl, self::PARAM_NAME);

        if (!$redirectUrl) {
            $redirectUrl = $this->getReserveUrl();
        } else if ($position !== false) {
            $borderPosition = $position + strlen(self::PARAM_NAME) + 1;
            $redirectUrl = mb_substr($redirectUrl, 0, $borderPosition) .
                urlencode(mb_substr($redirectUrl, $borderPosition, strlen($redirectUrl)));
        }

        return $redirectUrl;
    }

    /**
     * @return string
     */
    protected function getReserveUrl()
    {
        $routeToBeMatched = $this->router->match($this->request);
        $routePath = explode('/', $routeToBeMatched->getMatchedRouteName());

        array_pop($routePath);

        if (empty($routePath)) {
            return '';
        }

        $routeName = implode('/', $routePath);

        return $this->helperPluginManager->get('url')($routeName);
    }
}
