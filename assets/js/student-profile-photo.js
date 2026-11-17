(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var root = document.getElementById('profilePhotoDesk');
        if (!root) {
            return;
        }

        var searchUrl = root.getAttribute('data-search-url') || '';
        var saveUrl = root.getAttribute('data-save-url') || '';
        var searchInput = document.getElementById('photoSearch');
        var clearBtn = document.getElementById('photoClear');
        var resultsBox = document.getElementById('photoResults');
        var card = document.getElementById('photoStudentCard');
        var selected = null;
        var timer = null;
        var requestSeq = 0;
        var stream = null;
        var facing = 'user';
        var saving = false;

        function escapeHtml(text) {
            return String(text || '').replace(/[&<>"']/g, function (ch) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[ch];
            });
        }

        function setStatus(message, kind) {
            var box = document.getElementById('photoStatus');
            if (!message) {
                box.hidden = true;
                box.textContent = '';
                box.className = 'pp-status';
                return;
            }
            box.hidden = false;
            box.className = 'pp-status is-' + (kind || 'muted');
            box.textContent = message;
        }

        function setBusy(on) {
            saving = on;
            document.getElementById('photoFrame').classList.toggle('is-busy', on);
            document.getElementById('photoFile').disabled = on;
            root.querySelectorAll('button').forEach(function (button) {
                button.disabled = on;
            });
        }

        function showPhoto(url) {
            var frame = document.getElementById('photoFrame');
            if (!url) {
                frame.innerHTML = '<i class="fas fa-user" aria-hidden="true"></i>';
                return;
            }
            var fresh = url + (url.indexOf('?') === -1 ? '?' : '&') + 't=' + Date.now();
            frame.innerHTML = '<img src="' + escapeHtml(fresh) + '" alt="Current profile photo">';
        }

        function thumbHtml(student) {
            if (student.photo_url) {
                return '<span class="pp-thumb"><img src="' + escapeHtml(student.photo_url) + '" alt=""></span>';
            }
            var letter = String(student.student_fullname || student.student_id || '?').trim().charAt(0).toUpperCase();
            return '<span class="pp-thumb" aria-hidden="true">' + escapeHtml(letter) + '</span>';
        }

        function markSaved(on) {
            document.getElementById('photoFrame').classList.toggle('is-saved', on);
            document.getElementById('btnNext').hidden = !on;
        }

        function selectStudent(student) {
            selected = student;
            card.hidden = false;
            document.getElementById('photoName').textContent = student.student_fullname || 'Student';
            document.getElementById('photoMeta').textContent = student.student_id + (student.student_nic ? '  ·  NIC ' + student.student_nic : '');
            document.getElementById('photoCourse').textContent = [student.course_name, student.department_name, student.academic_year].filter(Boolean).join('  ·  ');
            showPhoto(student.photo_url || '');
            markSaved(false);
            hideResults();
            searchInput.value = student.student_id;
            clearBtn.hidden = false;
            setStatus('Take a photo or upload one. The face should fill the oval.', 'muted');
            stopCamera();
            if (window.matchMedia('(max-width: 767.98px)').matches) {
                card.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        function readyForNext() {
            searchInput.value = '';
            clearBtn.hidden = true;
            hideResults();
            var searchCard = document.querySelector('.pp-search');
            if (searchCard) {
                searchCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            searchInput.focus();
        }

        function hideResults() {
            resultsBox.hidden = true;
            resultsBox.innerHTML = '';
            searchInput.setAttribute('aria-expanded', 'false');
        }

        function renderResults(students) {
            if (!students.length) {
                resultsBox.innerHTML = '<div class="pp-note">No student matches that registration number or NIC.</div>';
                resultsBox.hidden = false;
                searchInput.setAttribute('aria-expanded', 'true');
                return;
            }
            resultsBox.innerHTML = students.map(function (student, index) {
                var meta = [student.student_nic ? 'NIC ' + student.student_nic : 'No NIC', student.course_name, student.department_name].filter(Boolean).join(' · ');
                return '<button type="button" class="pp-result" role="option" data-index="' + index + '">' +
                    thumbHtml(student) +
                    '<span class="pp-result-text">' +
                    '<strong>' + escapeHtml(student.student_id) + '</strong>' +
                    '<span class="pp-name">' + escapeHtml(student.student_fullname || 'Student') + '</span>' +
                    '<span class="pp-meta">' + escapeHtml(meta) + '</span>' +
                    '</span></button>';
            }).join('');
            resultsBox._students = students;
            resultsBox.hidden = false;
            searchInput.setAttribute('aria-expanded', 'true');
        }

        function runSearch(term) {
            var seq = ++requestSeq;
            resultsBox.innerHTML = '<div class="pp-note">Searching…</div>';
            resultsBox.hidden = false;
            searchInput.setAttribute('aria-expanded', 'true');
            fetch(searchUrl + '?q=' + encodeURIComponent(term), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (response) {
                return response.json();
            }).then(function (data) {
                if (seq !== requestSeq) {
                    return;
                }
                renderResults((data && data.students) || []);
            }).catch(function () {
                if (seq !== requestSeq) {
                    return;
                }
                resultsBox.innerHTML = '<div class="pp-note">Search failed. Check the connection and try again.</div>';
                resultsBox.hidden = false;
            });
        }

        function moveActive(delta) {
            var buttons = resultsBox.querySelectorAll('.pp-result');
            if (!buttons.length) {
                return;
            }
            var current = resultsBox.querySelector('.pp-result.is-active');
            var index = current ? Array.prototype.indexOf.call(buttons, current) : -1;
            index = index + delta;
            if (index < 0) {
                index = buttons.length - 1;
            }
            if (index >= buttons.length) {
                index = 0;
            }
            Array.prototype.forEach.call(buttons, function (button, i) {
                button.classList.toggle('is-active', i === index);
            });
            buttons[index].scrollIntoView({ block: 'nearest' });
        }

        function chooseActive() {
            var active = resultsBox.querySelector('.pp-result.is-active') || resultsBox.querySelector('.pp-result');
            if (!active || !resultsBox._students) {
                return;
            }
            selectStudent(resultsBox._students[parseInt(active.getAttribute('data-index'), 10)]);
        }

        searchInput.addEventListener('input', function () {
            var term = searchInput.value.trim();
            clearBtn.hidden = term === '';
            clearTimeout(timer);
            if (term.length < 2) {
                requestSeq += 1;
                hideResults();
                return;
            }
            timer = setTimeout(function () {
                runSearch(term);
            }, 250);
        });

        searchInput.addEventListener('keydown', function (event) {
            if (resultsBox.hidden) {
                return;
            }
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                moveActive(1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                moveActive(-1);
            } else if (event.key === 'Enter') {
                if (resultsBox.querySelector('.pp-result')) {
                    event.preventDefault();
                    chooseActive();
                }
            } else if (event.key === 'Escape') {
                hideResults();
            }
        });

        resultsBox.addEventListener('click', function (event) {
            var button = event.target.closest('.pp-result');
            if (!button || !resultsBox._students) {
                return;
            }
            selectStudent(resultsBox._students[parseInt(button.getAttribute('data-index'), 10)]);
        });

        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            clearBtn.hidden = true;
            hideResults();
            searchInput.focus();
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('.pp-search')) {
                hideResults();
            }
        });

        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(function (track) {
                    track.stop();
                });
                stream = null;
            }
            var video = document.getElementById('photoVideo');
            video.srcObject = null;
            document.getElementById('photoCam').hidden = true;
        }

        function paint(target, outW, outH) {
            var video = document.getElementById('photoVideo');
            if (!video.videoWidth) {
                return false;
            }
            target.width = outW;
            target.height = outH;
            var ctx = target.getContext('2d');
            var canvasAspect = outW / outH;
            var videoAspect = video.videoWidth / video.videoHeight;
            var drawW = videoAspect > canvasAspect ? outH * videoAspect : outW;
            var drawH = videoAspect > canvasAspect ? outH : outW / videoAspect;
            ctx.setTransform(1, 0, 0, 1, 0, 0);
            ctx.clearRect(0, 0, outW, outH);
            ctx.save();
            ctx.translate(outW / 2, outH / 2);
            if (facing === 'user') {
                ctx.scale(-1, 1);
            }
            ctx.drawImage(video, -drawW / 2, -drawH / 2, drawW, drawH);
            ctx.restore();
            return true;
        }

        function openCamera(nextFacing) {
            if (!selected) {
                setStatus('Select a student first.', 'danger');
                return;
            }
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                setStatus('This browser cannot use the camera. Upload a photo instead.', 'danger');
                return;
            }
            facing = nextFacing === 'environment' ? 'environment' : 'user';
            if (stream) {
                stream.getTracks().forEach(function (track) {
                    track.stop();
                });
                stream = null;
            }
            setStatus('Opening the camera…', 'muted');
            navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: facing },
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            }).then(function (next) {
                stream = next;
                var video = document.getElementById('photoVideo');
                var cam = document.getElementById('photoCam');
                video.srcObject = next;
                cam.hidden = false;
                cam.classList.toggle('is-mirror', facing === 'user');
                video.play();
                setStatus('Keep the face inside the oval, then capture.', 'muted');
            }).catch(function () {
                setStatus('Could not open the camera. Allow camera access, or upload a photo.', 'danger');
            });
        }

        function uploadBlob(blob, filename) {
            if (!selected) {
                setStatus('Select a student first.', 'danger');
                return;
            }
            if (blob.size > 5 * 1024 * 1024) {
                setStatus('Image must be 5MB or smaller.', 'danger');
                return;
            }
            var body = new FormData();
            body.append('student_id', selected.student_id);
            body.append('profile_image', blob, filename);
            setBusy(true);
            setStatus('Updating the profile photo…', 'muted');
            fetch(saveUrl, {
                method: 'POST',
                body: body,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (response) {
                return response.json();
            }).then(function (data) {
                if (!data || !data.success) {
                    throw new Error((data && data.message) ? data.message : 'Failed to update the profile photo.');
                }
                selected.photo_url = data.url || '';
                showPhoto(data.url || '');
                markSaved(true);
                var savedName = selected.student_fullname || selected.student_id;
                setStatus('Photo saved for ' + savedName + '. Check the profile, then collect the next student.', 'success');
                stopCamera();
                searchInput.value = '';
                clearBtn.hidden = true;
                document.getElementById('photoFrame').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }).catch(function (error) {
                setStatus(error.message || 'Failed to update the profile photo.', 'danger');
            }).then(function () {
                setBusy(false);
            });
        }

        document.getElementById('btnNext').addEventListener('click', readyForNext);
        document.getElementById('btnFront').addEventListener('click', function () {
            openCamera('user');
        });
        document.getElementById('btnBack').addEventListener('click', function () {
            openCamera('environment');
        });
        document.getElementById('btnCamCancel').addEventListener('click', function () {
            stopCamera();
            setStatus('Camera closed.', 'muted');
        });
        document.getElementById('btnCapture').addEventListener('click', function () {
            var canvas = document.getElementById('photoCapture');
            if (!paint(canvas, 826, 1062)) {
                setStatus('Wait for the camera picture, then capture again.', 'danger');
                return;
            }
            canvas.toBlob(function (blob) {
                if (blob) {
                    uploadBlob(blob, 'profile-photo.jpg');
                }
            }, 'image/jpeg', 0.92);
        });
        document.getElementById('photoFile').addEventListener('change', function () {
            var file = this.files && this.files[0];
            this.value = '';
            if (!file) {
                return;
            }
            if (!/^image\/(jpeg|png|gif)$/.test(file.type)) {
                setStatus('Please use a JPG, PNG, or GIF image.', 'danger');
                return;
            }
            uploadBlob(file, file.name || 'profile-photo.jpg');
        });

        searchInput.focus();
    });
}());
