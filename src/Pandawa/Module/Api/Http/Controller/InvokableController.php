<?php
/**
 * This file is part of the Pandawa package.
 *
 * (c) 2018 Pandawa <https://github.com/bl4ckbon3/pandawa>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pandawa\Module\Api\Http\Controller;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Pandawa\Component\Ddd\AbstractModel;
use Pandawa\Component\Message\AbstractQuery;
use Pandawa\Module\Api\Transformer\CollectionTransformer;
use Pandawa\Module\Api\Transformer\Transformer;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class InvokableController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function handle(Request $request)
    {
        $route = $request->route();

        $data = array_merge(
            $request->route()->parameters(),
            $request->all(),
            $request->files->all(),
            ['auth_user' => $request->getUser()]
        );

        $message = $this->getMessage($request);
        $message = new $message($data);

        if ($message instanceof AbstractQuery) {
            $this->modifyQuery($message, $request);
        }

        $result = $this->dispatch($message);

        $this->withRelations($result, $route->defaults);

        return $this->sendResponse($result);
    }

    private function sendResponse($results)
    {
        if ($results instanceof Collection || $results instanceof LengthAwarePaginator) {
            return new CollectionTransformer($results);
        }

        return new Transformer($results);
    }

    private function getMessage(Request $request): string
    {
        if (null !== $message = array_get($request->route()->defaults, 'message')) {
            return $message;
        }

        throw new InvalidArgumentException('Parameter "message" not found on route.');
    }

    private function modifyQuery(AbstractQuery $query, Request $request): void
    {
        $route = $request->route();

        if (null !== $withs = array_get($route->defaults, 'withs')) {
            $query->withRelations($withs);
        }

        if (true === array_get($route->defaults, 'paginate', false)) {
            $query->paginate($request->get('limit', 50));
        }
    }

    private function withRelations($stmt, array $options): void
    {
        if (null !== $withs = array_get($options, 'withs')) {
            $withs = array_map(
                function (string $rel) {
                    return Str::camel($rel);
                },
                $withs
            );

            if ($stmt instanceof Builder) {
                $stmt->with($withs);
            } else if ($stmt instanceof AbstractModel) {
                $stmt->load($withs);
            }
        }
    }
}
