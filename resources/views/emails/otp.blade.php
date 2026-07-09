<x-mail::message>
# Permintaan Reset Password

Kami menerima permintaan untuk melakukan reset password pada akun Anda. 
Berikut adalah kode OTP untuk melanjutkan proses reset password:

<x-mail::panel>
# {{ $otp }}
</x-mail::panel>

*Kode OTP ini hanya berlaku selama 10 menit.* Jika Anda tidak merasa melakukan permintaan ini, abaikan saja email ini.

Terima kasih,<br>
Tim {{ config('app.name') }}
</x-mail::message>
