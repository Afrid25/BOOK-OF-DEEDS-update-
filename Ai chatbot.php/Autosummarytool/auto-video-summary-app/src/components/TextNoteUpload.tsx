import React, { useState } from 'react';

const TextNoteUpload: React.FC = () => {
    const [file, setFile] = useState<File | null>(null);
    const [uploadProgress, setUploadProgress] = useState<number>(0);
    const [error, setError] = useState<string | null>(null);

    const handleFileChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const selectedFile = event.target.files?.[0];
        if (selectedFile && (selectedFile.type === 'text/plain' || selectedFile.type === 'application/pdf' || selectedFile.type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')) {
            setFile(selectedFile);
            setError(null);
        } else {
            setError('Please upload a valid TXT, PDF, or DOCX file.');
        }
    };

    const handleUpload = async () => {
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);

        try {
            const response = await fetch('/api/upload-text-note', {
                method: 'POST',
                body: formData,
                onUploadProgress: (progressEvent: ProgressEvent) => {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / (progressEvent.total || 1));
                    setUploadProgress(percentCompleted);
                },
            });

            if (!response.ok) {
                throw new Error('Upload failed');
            }

            // Handle successful upload response here
        } catch (err) {
            setError('An error occurred during the upload.');
        }
    };

    return (
        <div>
            <h2>Upload Text Note</h2>
            <input type="file" accept=".txt, .pdf, .docx" onChange={handleFileChange} />
            {error && <p style={{ color: 'red' }}>{error}</p>}
            <button onClick={handleUpload} disabled={!file}>Upload</button>
            {uploadProgress > 0 && <p>Upload Progress: {uploadProgress}%</p>}
        </div>
    );
};

export default TextNoteUpload;