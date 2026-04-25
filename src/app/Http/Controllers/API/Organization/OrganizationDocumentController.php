<?php

namespace App\Http\Controllers\API\Organization;

use App\Http\Controllers\Controller;
use App\Models\OrganizationDocument;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class OrganizationDocumentController extends Controller
{
    use ApiResponse;

    private function getUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return null;
        }
    }

    public function index()
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $org = $user->organization;
        if (!$org) return $this->error('Organization not found', 404);

        $documents = OrganizationDocument::where('organization_id', $org->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($documents, 'Documents retrieved');
    }

    public function store(Request $request)
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $org = $user->organization;
        if (!$org) return $this->error('Organization not found', 404);

        $request->validate([
            'document_type' => 'required|in:trade_license,ngo_certificate,tax_certificate,other',
            'document_url'  => 'required|string|url',
        ]);

        $document = OrganizationDocument::create([
            'organization_id' => $org->id,
            'document_type'   => $request->document_type,
            'document_url'    => $request->document_url,
        ]);

        return $this->success($document, 'Document added', 201);
    }

    public function destroy($id)
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $org = $user->organization;
        if (!$org) return $this->error('Organization not found', 404);

        $document = OrganizationDocument::where('id', $id)
            ->where('organization_id', $org->id)
            ->first();

        if (!$document) return $this->error('Document not found', 404);

        $document->delete();

        return $this->success(null, 'Document deleted');
    }
}
