<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function store(Request $request)
    {
        $e = new EmailTemplate();
        $e->name = $request->name;
        $e->template = $request->template;
        $e->organization()->associate(auth()->user()->currentLogin);
        $e->save();
        return $e;
    }


    public function index(Request $request)
    {

        return auth()->user()->currentLogin->emailTemplates;
    }

    public function show(Request $request, EmailTemplate $emailTemplate)
    {

        abort_unless($emailTemplate->organization()->is(auth()->user()->currentLogin), 401, 'Unauthenticated');
        return $emailTemplate;
    }

    public function destroy(Request $request, EmailTemplate $emailTemplate)
    {

        abort_unless($emailTemplate->organization()->is(auth()->user()->currentLogin), 401, 'Unauthenticated');
        abort_if(count($emailTemplate->messageTemplates), 401, 'This template is currently in use');
        $emailTemplate->delete();
        return;
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {

        abort_unless($emailTemplate->organization()->is(auth()->user()->currentLogin), 401, 'Unauthenticated');
        $emailTemplate->template = $request->template;
        $emailTemplate->name = $request->name;
        $emailTemplate->save();
        return $emailTemplate;
    }
}
