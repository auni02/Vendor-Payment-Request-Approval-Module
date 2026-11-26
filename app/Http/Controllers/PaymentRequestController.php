<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Requests\PaymentRequestFormRequest;
use App\Models\PaymentRequest;
use App\Services\PaymentRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentRequestController extends Controller
{
    public function __construct(private readonly PaymentRequestService $service) {}

    /** Dashboard: status totals plus the filterable request listing. */
    public function index(Request $request): View
    {
        $user = $request->user();
        [$filters, $filterErrors] = $this->filters($request);

        $totals = PaymentRequest::visibleTo($user)
            ->selectRaw('status, COUNT(*) as total, COALESCE(SUM(amount), 0) as amount')
            ->groupBy('status')
            ->get()
            ->keyBy(fn ($row) => $row->status->value);

        $stats = collect(PaymentStatus::cases())->mapWithKeys(fn (PaymentStatus $s) => [$s->value => [
            'label' => $s->label(),
            'count' => (int) ($totals[$s->value]->total ?? 0),
            'amount' => (float) ($totals[$s->value]->amount ?? 0),
        ]]);

        $requests = PaymentRequest::visibleTo($user)
            ->with('requester:id,name')
            ->filter($filters)
            ->latest()
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('payment-requests.index', [
            'stats' => $stats,
            'requests' => $requests,
            'filters' => $filters,
            'filterErrors' => $filterErrors,
            'statuses' => PaymentStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return view('payment-requests.create', [
            'paymentRequest' => new PaymentRequest(['payment_method' => PaymentMethod::BankTransfer]),
            'methods' => PaymentMethod::cases(),
        ]);
    }

    public function store(PaymentRequestFormRequest $request): RedirectResponse
    {
        $paymentRequest = $this->service->create($request->user(), $request->requestData(), $request->file('attachment'));

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', "Payment request {$paymentRequest->reference} submitted and pending approval.");
    }

    public function show(PaymentRequest $paymentRequest): View
    {
        $paymentRequest->load(['requester:id,name,email', 'reviewer:id,name', 'activities.user:id,name,role']);

        return view('payment-requests.show', [
            'paymentRequest' => $paymentRequest,
            'version' => PaymentRequestService::versionOf($paymentRequest),
        ]);
    }

    public function edit(PaymentRequest $paymentRequest): View
    {
        return view('payment-requests.edit', [
            'paymentRequest' => $paymentRequest,
            'methods' => PaymentMethod::cases(),
        ]);
    }

    public function update(PaymentRequestFormRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        $changed = $this->service->update(
            $request->user(),
            $paymentRequest,
            $request->requestData(),
            $request->file('attachment'),
            $request->boolean('remove_attachment'),
        );

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with($changed ? 'success' : 'info', $changed ? 'Payment request updated.' : 'No changes were made.');
    }

    /** Stream the private attachment to an authorised user. */
    public function attachment(PaymentRequest $paymentRequest): StreamedResponse
    {
        $disk = Storage::disk(PaymentRequestService::DISK);
        abort_unless($paymentRequest->attachment_path && $disk->exists($paymentRequest->attachment_path), 404);

        return $disk->download($paymentRequest->attachment_path, $paymentRequest->attachment_name, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Validate listing filters without redirecting: invalid values are
     * dropped and reported, so a bad query string never breaks the page.
     *
     * @return array{0: array<string, string>, 1: MessageBag}
     */
    private function filters(Request $request): array
    {
        $rules = [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'date_field' => ['nullable', Rule::in(['created_at', 'payment_date'])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];

        $input = array_map(
            fn ($v) => is_string($v) ? trim($v) : $v,
            $request->only(array_keys($rules)),
        );
        $validator = Validator::make($input, $rules, [], [
            'q' => 'search', 'date_from' => 'from date', 'date_to' => 'to date',
        ]);
        $errors = $validator->errors();

        $filters = collect($input)
            ->reject(fn ($value, $key) => $errors->has($key) || $value === null || $value === '' || ! is_string($value))
            ->all();
        $filters['date_field'] ??= 'created_at';

        return [$filters, $errors];
    }
}
