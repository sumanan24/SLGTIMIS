<div class="container-fluid py-3" style="max-width: 860px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <h4 class="mb-0" style="color: #0d6efd;">Mobitel SMS gateway test</h4>
        </div>
        <div class="card-body">
            <p class="text-secondary">This check uses the official Mobitel PHP SOAP session. The username, password, and alias stay on the server and are not shown here. Response code 200 means Mobitel accepted the message. It does not by itself confirm the handset received it.</p>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="<?php echo APP_URL; ?>/admin/sms-gateway">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="test_mobile">Authorized test mobile</label>
                    <input type="text" class="form-control" id="test_mobile" name="test_mobile" inputmode="numeric" placeholder="07XXXXXXXX" maxlength="15">
                    <div class="form-text">Check gateway tests the session only. Send Test SMS sends one message to this number.</div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-outline-primary" name="send_test" value="0">Check gateway</button>
                    <button type="submit" class="btn btn-primary" name="send_test" value="1">Send Test SMS</button>
                </div>
                <div class="form-text mt-2">Send Test SMS uses the official API and this text: SLGTI MIS Mobitel SMS Gateway Test. SMS integration is working.</div>
            </form>
            <?php if (!empty($results)): ?>
                <div class="table-responsive mt-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr><th>Check</th><th>Result</th><th>Detail</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) $row['name']); ?></td>
                                    <td><?php echo !empty($row['ok']) ? 'Passed' : 'Failed'; ?></td>
                                    <td><?php echo htmlspecialchars((string) $row['detail']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
