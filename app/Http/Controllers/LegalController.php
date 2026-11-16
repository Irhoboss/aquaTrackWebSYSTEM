<?php

namespace App\Http\Controllers;

use App\Support\Setting;
use Illuminate\Contracts\View\View;

class LegalController extends Controller
{
    public function privacy(): View
    {
        return $this->page('Privacy policy', [
            ['Your information', [
                'AquaTrack uses account name, username, email address, and an optional phone number to create and secure an account. Orders require a recipient name, contact number, delivery address, selected items, and any note you choose to provide.',
                'The system also processes sensor readings and operational records. Security logs may include request metadata needed to protect accounts and investigate abuse.',
            ]],
            ['How information is used', [
                'Information is used to authenticate users, manage station operations, fulfil and update orders, send requested verification messages, and protect the service. We do not sell personal information or use it for targeted advertising.',
                'Google receives information when you choose Google sign-in. Email and hosting providers may process information to deliver the service; the exact provider depends on the deployment configuration.',
            ]],
            ['Retention and your choices', [
                'Buyer customers may update their profile or delete their account from the profile page. Staff and operational account holders should contact the station owner or configured privacy contact to request deletion. When a buyer account is deleted, contact details are removed from retained order records; non-identifying order records may remain for fulfilment, operational, or legal recordkeeping.',
                'Some security records may be retained only as long as needed for service protection. No fixed retention period has been configured in this application.',
            ]],
            ['Children', [
                'A person under 18 may use an account only when a parent or legal guardian creates or supervises the account and provides consent. The app records an age/guardian acknowledgement, not a date of birth.',
            ]],
            ['Contact and operator', [
                'The operator name, business address, and privacy contact shown below are configured by the station. If any field is missing, the operator must add accurate details in Settings before public launch.',
            ]],
        ]);
    }

    public function terms(): View
    {
        return $this->page('Terms of service', [
            ['Using AquaTrack', [
                'Provide accurate account and delivery information, keep your sign-in credentials private, and use only the station features available to your account role.',
                'You may not interfere with devices, submit false readings, access another person’s account, or use the service to upload unlawful or harmful content.',
            ]],
            ['Orders and prices', [
                'Product prices and any configured delivery fee are shown before an order is placed. The total shown at checkout is the amount recorded for that order. No additional fee should be charged unless it is disclosed and agreed before fulfilment.',
                'An order is subject to stock availability and confirmation by the station. You may contact the station to ask about or cancel an order before fulfilment.',
            ]],
            ['Monitoring information', [
                'Sensor readings and pH thresholds are operational monitoring aids. They do not certify that water is safe or potable and do not replace laboratory testing or regulatory requirements.',
            ]],
            ['Accounts and age', [
                'You must be at least 18, or have a parent/legal guardian create or supervise your account and consent to its use. The operator may suspend an account used to compromise the service or other users.',
            ]],
            ['Changes and consumer rights', [
                'These draft terms do not limit rights that cannot be waived under applicable law. The operator should replace this text with terms reviewed for the business location and applicable law before launch.',
            ]],
        ]);
    }

    public function refunds(): View
    {
        return $this->page('Refund and cancellation policy', [
            ['Before fulfilment', [
                'Contact the station promptly if you need to cancel or change an order. The station should confirm whether it can cancel the order before preparation or dispatch.',
            ]],
            ['Incorrect, damaged, or undelivered orders', [
                'Contact the station through the account support channel with the order number and a description of the issue. The station will review the order and explain the available remedy, including replacement or refund where appropriate under applicable consumer law.',
                'No fixed reporting deadline or automatic refund window is configured in this app. Applicable statutory rights take precedence.',
            ]],
            ['Fees and refunds', [
                'The amount shown at checkout includes the listed products and any delivery fee configured by the station. Do not charge a fee that was not disclosed and accepted before fulfilment.',
                'The app does not process online card payments. Any refund method and timing must be agreed with the customer and follow applicable law.',
            ]],
            ['Operator details', [
                'The station operator must configure accurate business and contact details below and in Settings before accepting public orders.',
            ]],
        ]);
    }

    public function cookies(): View
    {
        return $this->page('Cookie policy', [
            ['Essential storage', [
                'AquaTrack uses an essential session cookie for sign-in, session security, and form protection. The interface also stores your theme and cookie-notice choice in your browser’s local storage; these values are not used for advertising.',
            ]],
            ['Optional tracking', [
                'No analytics or advertising cookies are enabled by this application. The cookie notice can be reopened from the page footer.',
            ]],
            ['Third-party requests', [
                'Where used, Bootstrap, Bootstrap Icons, Chart.js, QRCode.js, and html5-qrcode are loaded from jsDelivr, cdnjs, or unpkg. These content-delivery providers may receive technical request data, including your IP address and browser information. Google also processes sign-in data if you choose Google sign-in.',
                'The operator should review these providers and their terms before launch. This application does not currently offer analytics or marketing-cookie categories.',
            ]],
            ['Manage your choice', [
                'You can clear the notice choice from your browser storage or reopen the notice from the footer. Blocking essential cookies may prevent sign-in and other session-based features from working.',
            ]],
        ]);
    }

    private function page(string $title, array $sections): View
    {
        return view('legal.show', [
            'title' => $title,
            'sections' => $sections,
            'operatorName' => Setting::get('legal_operator_name'),
            'stationName' => Setting::get('station_name'),
            'stationAddress' => Setting::get('station_address'),
            'stationContact' => Setting::get('station_contact'),
            'privacyContact' => Setting::get('privacy_contact_email'),
        ]);
    }
}
