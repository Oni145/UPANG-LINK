<?php
class MiddlewarePipeline {
    private $middlewares = [];
    private $handler;

    // The final handler (controller action) to be called after all middleware.
    public function __construct(callable $handler) {
        $this->handler = $handler;
    }

    // Add a middleware function to the pipeline.
    public function add(callable $middleware) {
        $this->middlewares[] = $middleware;
    }

    // Run the middleware pipeline with the given request data.
    public function run($request) {
        $handler = $this->handler;
        // Wrap each middleware in reverse order so they run in the order added.
        foreach (array_reverse($this->middlewares) as $middleware) {
            $next = $handler;
            $handler = function ($request) use ($middleware, $next) {
                return $middleware($request, $next);
            };
        }
        return $handler($request);
    }
}
?>
