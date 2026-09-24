<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\MfaService;
use Illuminate\Http\Request;

class MfaController extends Controller
{
    public function __construct(private MfaService $mfa) {}

    public function show(Request $request)
    {
        $user = $request->user();

        return view('auth.mfa', [
            'channel'     => $request->session()->get('mfa.channel', $user->mfa_channel),
            'emailMask'   => $user->maskedDestination('email'),
            'smsMask'     => $user->maskedDestination('sms'),
            'sent'        => $request->session()->get('mfa.sent', false),
        ]);
    }

    public function send(Request $request)
    {
        $data = $request->validate(['channel' => ['required','in:email,sms']]);

        $this->mfa->issue($request->user(), $data['channel']);

        $request->session()->put('mfa.channel', $data['channel']);
        $request->session()->put('mfa.sent', true);

        return back()->with('status', 'A code is on its way. It is valid for five minutes.');
    }

    public function verify(Request $request)
    {
        $data = $request->validate(['code' => ['required','digits:6']]);

        $result = $this->mfa->verify($request->user(), $data['code']);

        if (! $result['ok']) {
            return back()->withErrors(['code' => $result['reason']]);
        }

        $request->session()->put('mfa.verified', true);
        $request->session()->regenerate();                 // new id once fully authenticated

        return redirect()->intended(route('dashboard'));
    }
}
