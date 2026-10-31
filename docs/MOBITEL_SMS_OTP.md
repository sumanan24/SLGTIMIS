# Mobitel SMS password reset

The forgot-password pages use the existing `user` login records. Students are matched by username (`student_id`) or NIC. Staff are matched by username (`staff_id`) or NIC. The SMS is sent only to the mobile already stored for that person: `student.student_phone` (or `student_whatsapp` when the phone is not a valid mobile) and `staff.staff_pno`.

## Gateway

The official Mobitel PHP SDK in `services/mobitel/ESMSWS.php` is used unchanged except `getClient()`, which reads `MOBITEL_SMS_WSDL` from the server environment. Session, send, renew, delivery-report, and close calls are the vendor functions.

- WSDL: `https://msmsenterpriseapi.mobitel.lk/mSMSEnterpriseAPI/mSMSEnterpriseAPI.wsdl`
- Flow: `createSession('', username, password, '')`, then `isSession($session)`, then `sendMessages($session, alias, message, [recipient], 0)`, then `closeSession($session)`
- Message type `0` is a normal SMS. Password reset never uses promotional type `1`.
- Return code `200` means the gateway accepted the message. It does not confirm handset delivery.
- `MOBITEL_ACCOUNT_NO` is not used.
- The approved sender alias for this account is `SLGTI`, stored as `MOBITEL_SMS_ALIAS`.

```env
MOBITEL_SMS_USERNAME=esmsusr_5btHPdML
MOBITEL_SMS_PASSWORD=
MOBITEL_SMS_ALIAS=SLGTI
MOBITEL_SMS_WSDL=https://msmsenterpriseapi.mobitel.lk/mSMSEnterpriseAPI/mSMSEnterpriseAPI.wsdl
MOBITEL_SMS_MESSAGE_TYPE=0
```

Put the API password only in the server `.env` file. On https://msmsenterprise.mobitel.lk choose Forgot Password, select API, and set an API password, then save it as `MOBITEL_SMS_PASSWORD`. The website login password is not accepted by `createSession`. An administrator can open `/admin/sms-gateway` to check configuration and send one test SMS. A gateway code of 200 means Mobitel accepted the message. It does not confirm handset delivery. A user can request at most three OTPs in 15 minutes.

## Password storage

New passwords are stored with `password_hash()`, which the existing login already checks through `password_verify()`. The reset does not sign the person in.

## Database

`database/migrations/2026_10_31_password_reset_otps.sql`

The same tables are created on the first reset request when they are missing.
