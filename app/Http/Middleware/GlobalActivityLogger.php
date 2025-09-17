<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class GlobalActivityLogger
{
    // app/Http/Middleware/GlobalActivityLogger.php
private $routePatterns = [
    // Facilitation
    'POST /facilitation/.*/receipt' => 'Uploaded facilitation receipt',
    'DELETE /facilitation/.*/receipt' => 'Deleted facilitation receipt', 
    'POST /frequest/store' => 'Created facilitation request',
    'POST /frequest/update' => 'Updated facilitation request',
    'POST /approvefrequest/.*' => 'Approved facilitation request',
    'POST /rejectfrequest/.*' => 'Rejected facilitation request',
    
    // Users Management
    'POST /user/store' => 'Created user',
    'POST /user/update' => 'Updated user',
    'DELETE /users/.*' => 'Deleted user',
    'POST /users/restore/.*' => 'Restored user',
    'POST /users/reset-password' => 'Reset user password',
    
    // Car Import Operations
    'POST /carimport/store' => 'Created car import',
    'POST /carimport/update' => 'Updated car import',
    'POST /carimport/winbid/.*' => 'Won car bid',
    'POST /carimport/losebid/.*' => 'Lost car bid',
    'POST /carimport/confirmfullpayment/.*' => 'Confirmed full payment',
    'POST /import/portcharges/.*' => 'Added port charges',
    'POST /import/confirmimported/.*' => 'Confirmed car imported',
    'POST /import/confirmimport/.*' => 'Confirmed import process',
    'POST /confirm-reception/.*' => 'Confirmed car reception',
    'POST /deposit/update' => 'Updated deposit amount',
    'POST /fullpayment/update' => 'Updated full payment',
    
    // Vehicle Inspections
    'POST /vehicle-inspection-submit' => 'Created vehicle inspection',
    'PUT /vehicle-inspections/.*' => 'Updated vehicle inspection',
    'POST /inspection/.*/photos/upload' => 'Uploaded inspection photos',
    'DELETE /inspection/.*/photos/.*' => 'Deleted inspection photo',
    
    // Sales Operations
    'POST /incash/store' => 'Created cash sale',
    'POST /incash/update' => 'Updated cash sale',
    'POST /incash/approve' => 'Approved cash sale',
    'POST /incash/delete' => 'Deleted cash sale',
    'POST /paymentForm/store' => 'Recorded payment',
    
    // Hire Purchase
    'POST /hirepurchase/store' => 'Created hire purchase',
    'POST /hirepurchase/update' => 'Updated hire purchase',
    'POST /hirepurchase/delete' => 'Deleted hire purchase',
    'POST /hirepurchase/confirm' => 'Confirmed hire purchase',
    'POST /HirePurchase/approve' => 'Approved hire purchase',
    'POST /hire-purchase' => 'Created HP agreement',
    'POST /hire-purchase/payments/store' => 'Recorded HP payment',
    'POST /hire-purchase/.*/approve' => 'Approved HP agreement',
    'POST /hire-purchase/payments/.*/verify' => 'Verified HP payment',
    'POST /hire-purchase/record-payment' => 'Recorded HP payment',
    'DELETE /hire-purchase/.*' => 'Deleted HP agreement',
    'POST /hire-purchase/.*/reminder' => 'Sent payment reminder',
    'POST /hire-purchase/lump-sum-payment' => 'Made lump sum payment',
    
    // Gentleman Agreement
    'POST /gentlemanagreement' => 'Created gentleman agreement',
    'POST /gentlemanagreement/.*/approve' => 'Approved gentleman agreement',
    'POST /gentlemanagreement/payment' => 'Recorded GA payment',
    'POST /gentlemanagreement/payments/.*/verify' => 'Verified GA payment',
    'DELETE /gentlemanagreement/.*' => 'Deleted GA agreement',
    'POST /gentlemanagreement/.*/reminder' => 'Sent GA reminder',
    
    // Fleet Management
    'POST /fleetacquisition' => 'Created fleet acquisition',
    'POST /fleetacquisition/.*/approve' => 'Approved fleet acquisition',
    'POST /fleetacquisition/.*/payments' => 'Recorded fleet payment',
    'POST /fleetacquisition/.*/delete-photo' => 'Deleted fleet photo',
    'DELETE /fleetacquisition/.*' => 'Deleted fleet acquisition',
    'POST /fleet-payments/.*/confirm' => 'Confirmed fleet payment',
    
    // Leads Management
    'POST /leads' => 'Created lead',
    'POST /leads/.*(?!/bulk-update|/export|/data)' => 'Updated lead',
    'DELETE /leads/.*' => 'Deleted lead',
    'POST /leads/bulk-update' => 'Bulk updated leads',
    
    // Leave Management
    'POST /leaves/store' => 'Created leave application',
    'POST /leaves/.*/approve' => 'Approved leave',
    'POST /leaves/.*/reject' => 'Rejected leave',
    'POST /leaves/.*/cancel' => 'Cancelled leave',
    'POST /leave-applications' => 'Created leave application',
    'POST /leave-applications/.*/approve' => 'Approved leave application',
    'POST /leave-applications/.*/reject' => 'Rejected leave application',
    'POST /leave-applications/.*/cancel' => 'Cancelled leave application',
    
    // Trade-in Management
    'POST /tradeinform/store' => 'Created trade-in',
    'PUT /tradein/.*' => 'Updated trade-in',
    'DELETE /vehicle/.*' => 'Deleted vehicle',
    'POST /vehicle/restore/.*' => 'Restored vehicle',
    
    // Logbooks Management
    'POST /logbooks' => 'Created logbook',
    'PUT /logbooks/.*' => 'Updated logbook',
    'DELETE /logbooks/.*' => 'Deleted logbook',
    'POST /logbooks/.*/upload-documents' => 'Uploaded logbook documents',
    'DELETE /logbooks/.*/documents/.*' => 'Deleted logbook document',
    'POST /logbooks/.*/archive' => 'Archived logbook',
    'POST /logbooks/.*/restore' => 'Restored logbook',
    'POST /upload-logbook' => 'Uploaded logbook file',
    'DELETE /logbooks/.*' => 'Deleted logbook file',
    
    // Agreement Files
    'POST /upload-agreement' => 'Uploaded agreement file',
    'DELETE /agreements/.*' => 'Deleted agreement file',
    
    // Loan Restructuring
    'POST /loan-restructuring/process' => 'Processed loan restructuring',
    
    // Gentleman Loan Restructuring
    'GET /gentleman-loan-restructuring/.*/options' => 'Viewed GA restructuring options',
    'POST /gentleman-loan-restructuring/get-options' => 'Got GA restructuring options',
    'POST /gentleman-loan-restructuring/process' => 'Processed GA restructuring',
    
    // Penalties
    'POST /.*/penalties/calculate' => 'Calculated penalties',
    'POST /penalties/.*/pay' => 'Paid penalty',
    'PUT /penalties/.*/waive' => 'Waived penalty',
    
];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (Auth::check() && $this->shouldLog($request, $response)) {
            $this->logActivity($request, $response);
        }

        return $response;
    }

    private function shouldLog($request, $response)
    {
        // Only log successful requests
        if ($response->getStatusCode() >= 400) {
            return false;
        }

        // Skip these routes
        $skipRoutes = ['/', 'dashboard', 'logout', 'login', 'redirect-home'];
        $routeName = $request->route()?->getName();
        
        return !in_array($routeName, $skipRoutes);
    }

    private function logActivity($request, $response)
    {
        $action = $this->detectAction($request);
        
        if ($action) {
            activity()
                ->causedBy(Auth::user())
                ->withProperties([
                    'ip' => $request->ip(),
                    'route' => $request->route()?->getName(),
                    'method' => $request->method(),
                    'url' => $request->getRequestUri(),
                    'status' => $response->getStatusCode(),
                    'user_role' => Auth::user()->role,
                    'user_name' =>Auth::user()->first_name . ' ' . Auth::user()->last_name, // Add this line
                    'user_email' => Auth::user()->email, // Optional: add email too
                    'request_data' => $this->getFilteredRequestData($request),
                ])
                ->log($action);
        }
    }

    private function detectAction($request)
    {
        $method = $request->method();
        $uri = $request->getRequestUri();
        $fullPattern = "$method $uri";

        foreach ($this->routePatterns as $pattern => $action) {
            if (preg_match("#^$pattern$#", $fullPattern)) {
                return $action;
            }
        }

        return null;
    }

    private function getFilteredRequestData($request)
    {
        $data = $request->except(['password', 'password_confirmation', '_token', '_method']);
        
        // Limit to prevent large logs
        return array_slice($data, 0, 5, true);
    }
}