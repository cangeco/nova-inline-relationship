<?php

namespace KirschbaumDevelopment\NovaInlineRelationship;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Laravel\Nova\Http\Requests\NovaRequest;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class NovaInlineRelationshipRequest extends NovaRequest
{
    public function duplicate(array $query = null, array $request = null, array $attributes = null, array $cookies = null, array $files = null, array $server = null)
    {
        return parent::duplicate($query, $request, $attributes, $cookies, $files, $server);
    }

    public function updateFiles(array $files)
    {
        if (! empty($files)) {
            $this->clearConvertedFiles();

            foreach ($files as $key => $file) {
                if ($file instanceof UploadedFile) {
                    $this->convertedFiles[$key] = $file;
                }
            }
        }
    }

    public function setRouteParams(array $params)
    {
        foreach ($params as $key => $value) {
            $this->route()->setParameter($key, $value);
        }
    }

    public static function createFromNovaRequest(NovaRequest $request)
    {
        (new static)->duplicate($request);
    }

    /**
     * Create an Illuminate request from a Symfony instance.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     *
     * @return static
     */
    public static function createFromBase(SymfonyRequest $request)
    {
        if ($request instanceof static) {
            return $request;
        }

        $content = $request->content;

        $newRequest = (new static)->duplicate(
            $request->query->all(),
            $request->request->all(),
            $request->attributes->all(),
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all()
        );

        $newRequest->headers->replace($request->headers->all());

        $newRequest->content = $content;

        $newRequest->request = $newRequest->getInputSource();

        $newRequest->setRouteResolver(function () {
        });

        return $newRequest;
    }

    /**
     * Create a new request instance from the given Laravel request.
     *
     * @param  \Illuminate\Http\Request  $from
     * @param  \Illuminate\Http\Request|null  $to
     *
     * @return static
     */
    public static function createFrom(Request $from, $to = null)
    {
        $request = $to ?: new static;

        $files = $from->files->all();

        $files = is_array($files) ? array_filter($files) : $files;

        $request->initialize(
            $from->query->all(),
            $from->request->all(),
            $from->attributes->all(),
            $from->cookies->all(),
            $files,
            $from->server->all(),
            $from->getContent()
        );

        $request->headers->replace($from->headers->all());

        $request->setJson($from->json());

        if ($session = $from->getSession()) {
            $request->setLaravelSession($session);
        }

        $request->setUserResolver($from->getUserResolver());

        $request->setRouteResolver($from->getRouteResolver());

        return $request;
    }

    public function clearConvertedFiles()
    {
        $this->convertedFiles = null;
    }
}