<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    header('Content-Type: application/json');

    // Config
    $allowed_types = ['mp4', 'mp3', 'wav', 'txt', 'pdf', 'docx'];
    $upload_dir = __DIR__ . '/uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    // Helper: Secure file upload
    function handle_upload($file) {
        global $allowed_types, $upload_dir;
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_types)) return ['error' => 'Invalid file type'];
        $filename = uniqid() . '.' . $ext;
        $target = $upload_dir . $filename;
        if (move_uploaded_file($file['tmp_name'], $target)) {
            return ['success' => true, 'path' => $target, 'ext' => $ext];
        }
        return ['error' => 'Upload failed'];
    }

    // Helper: Whisper API transcription (pseudo-code)
    function transcribe_file($filepath) {
        // TODO: Implement Whisper API call
        // Example: Send $filepath to Whisper API and get transcription
        return "Transcribed text from Whisper API";
    }

    // Helper: Extract text from notes (pseudo-code)
    function extract_text($filepath, $ext) {
        // TODO: Implement extraction for txt, pdf, docx
        // Example: Read file and extract text
        return "Extracted text from note file";
    }

    // Helper: OpenAI API call (pseudo-code)
    function openai_api($prompt, $task = 'summary') {
        // TODO: Implement OpenAI API call
        // Example: Send $prompt to OpenAI API for summary or slide generation
        if ($task === 'summary') {
            return "AI-generated summary of the content.";
        } else if ($task === 'slides') {
            return [
                ['title' => 'Slide 1', 'bullets' => ['Point A', 'Point B']],
                ['title' => 'Slide 2', 'bullets' => ['Point C', 'Point D']]
            ];
        }
        return null;
    }

    $upload = handle_upload($_FILES['file']);
    if (isset($upload['error'])) {
        echo json_encode(['error' => $upload['error']]);
        exit;
    }
    $text = '';
    if (in_array($upload['ext'], ['mp4', 'mp3', 'wav'])) {
        $text = transcribe_file($upload['path']);
    } else {
        $text = extract_text($upload['path'], $upload['ext']);
    }
    $summary = openai_api($text, 'summary');
    $slides = openai_api($summary, 'slides');
    echo json_encode([
        'summary' => $summary,
        'slides' => $slides
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['updateSlides']) && isset($input['summary'])) {
        $summary = $input['summary'];
        $slides = openai_api($summary, 'slides');
        echo json_encode(['slides' => $slides]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Auto Video Summary & Slide Builder</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2em; background: #f7f7f7; }
        .card { background: #fff; padding: 1em; margin: 1em 0; border-radius: 8px; box-shadow: 0 2px 8px #ccc; }
        .slides { display: flex; flex-direction: column; gap: 1em; }
        .slide { background: #e3f2fd; padding: 1em; border-radius: 6px; }
        .hidden { display: none; }
        #nav { margin: 1em 0; }
    </style>
</head>
<body>
    <h2>Auto Video Summary & Slide Builder</h2>
    <form id="uploadForm" enctype="multipart/form-data" method="post">
        <input type="file" name="file" required>
        <select name="language" id="language">
            <option value="en">English</option>
            <option value="es">Spanish</option>
            <option value="fr">French</option>
            <!-- Add more as needed -->
        </select>
        <button type="submit">Upload & Summarize</button>
    </form>
    <div id="status"></div>
    <div id="summaryCard" class="card hidden"></div>
    <textarea id="editSummary" class="hidden" rows="6" style="width:100%"></textarea>
    <button id="updateSlidesBtn" class="hidden">Update Slides</button>
    <button id="convertBtn" class="hidden">Convert to Slides</button>
    <div id="slidesContainer" class="slides hidden"></div>
    <div id="nav" class="hidden">
        <button id="prevBtn">Previous</button>
        <span id="slideNum"></span>
        <button id="nextBtn">Next</button>
    </div>
    <button id="downloadPdfBtn" class="hidden">Download Slides as PDF</button>
    <script>
        const form = document.getElementById('uploadForm');
        const status = document.getElementById('status');
        const summaryCard = document.getElementById('summaryCard');
        const editSummary = document.getElementById('editSummary');
        const updateSlidesBtn = document.getElementById('updateSlidesBtn');
        const convertBtn = document.getElementById('convertBtn');
        const slidesContainer = document.getElementById('slidesContainer');
        const nav = document.getElementById('nav');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const slideNum = document.getElementById('slideNum');
        const downloadPdfBtn = document.getElementById('downloadPdfBtn');

        let slides = [];
        let currentSlide = 0;

        form.onsubmit = async (e) => {
            e.preventDefault();
            status.textContent = 'Uploading...';
            summaryCard.classList.add('hidden');
            convertBtn.classList.add('hidden');
            slidesContainer.classList.add('hidden');
            nav.classList.add('hidden');
            const data = new FormData(form);
            try {
                const res = await fetch(window.location.pathname, { method: 'POST', body: data });
                const json = await res.json();
                if (json.error) {
                    status.textContent = json.error;
                } else {
                    status.textContent = 'Upload complete!';
                    summaryCard.textContent = json.summary;
                    editSummary.value = json.summary;
                    editSummary.classList.remove('hidden');
                    updateSlidesBtn.classList.remove('hidden');
                    slides = json.slides;
                }
            } catch (err) {
                status.textContent = 'Error uploading file.';
            }
        };

        updateSlidesBtn.onclick = async () => {
            status.textContent = 'Updating slides...';
            try {
                const res = await fetch(window.location.pathname, {
                    method: 'POST',
                    body: JSON.stringify({ summary: editSummary.value, updateSlides: true }),
                    headers: { 'Content-Type': 'application/json' }
                });
                const json = await res.json();
                slides = json.slides;
                showSlide(0);
                slidesContainer.classList.remove('hidden');
                nav.classList.remove('hidden');
                status.textContent = 'Slides updated!';
            } catch (err) {
                status.textContent = 'Error updating slides.';
            }
        };

        convertBtn.onclick = () => {
            if (!slides || slides.length === 0) return;
            showSlide(0);
            slidesContainer.classList.remove('hidden');
            nav.classList.remove('hidden');
            downloadPdfBtn.classList.remove('hidden');
        };

        downloadPdfBtn.onclick = () => {
            import('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js').then(jsPDF => {
                const doc = new jsPDF.jsPDF();
                slides.forEach((slide, i) => {
                    doc.setFontSize(16);
                    doc.text(slide.title, 10, 20);
                    doc.setFontSize(12);
                    slide.bullets.forEach((b, idx) => {
                        doc.text(`• ${b}`, 15, 30 + idx * 10);
                    });
                    if (i < slides.length - 1) doc.addPage();
                });
                doc.save('slides.pdf');
            });
        };

        function showSlide(idx) {
            slidesContainer.innerHTML = '';
            const slide = slides[idx];
            const div = document.createElement('div');
            div.className = 'slide';
            div.innerHTML = `<h3>${slide.title}</h3><ul>${slide.bullets.map(b => `<li>${b}</li>`).join('')}</ul>`;
            slidesContainer.appendChild(div);
            nextBtn.disabled = idx === slides.length - 1;
            prevBtn.disabled = idx === 0;
            slideNum.textContent = `Slide ${idx + 1} of ${slides.length}`;
            currentSlide = idx;
        }

        nextBtn.onclick = () => {
            if (currentSlide < slides.length - 1) showSlide(currentSlide + 1);
        };

        prevBtn.onclick = () => {
            if (currentSlide > 0) showSlide(currentSlide - 1);
        };
    </script>
</body>
</html>