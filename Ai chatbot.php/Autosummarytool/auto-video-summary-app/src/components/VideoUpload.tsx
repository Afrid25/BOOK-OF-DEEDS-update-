import React, { useState } from 'react';

const VideoUpload: React.FC = () => {
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [uploadProgress, setUploadProgress] = useState<number>(0);
    const [uploading, setUploading] = useState<boolean>(false);

    const handleFileChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        if (event.target.files && event.target.files[0]) {
            setSelectedFile(event.target.files[0]);
        }
    };

    const handleUpload = async () => {
        if (!selectedFile) return;

        const formData = new FormData();
        formData.append('video', selectedFile);

        setUploading(true);
        setUploadProgress(0);

        try {
            const response = await fetch('/api/upload-video', {
                method: 'POST',
                body: formData,
                onUploadProgress: (progressEvent: ProgressEvent) => {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / (progressEvent.total || 1));
                    setUploadProgress(percentCompleted);
                },
            });

            if (response.ok) {
                // Handle successful upload
            } else {
                // Handle upload error
            }
        } catch (error) {
            console.error('Upload failed:', error);
        } finally {
            setUploading(false);
        }
    };

    return (
        <div>
            <h2>Upload Educational Video</h2>
            <input type="file" accept="video/mp4" onChange={handleFileChange} />
            <button onClick={handleUpload} disabled={uploading || !selectedFile}>
                {uploading ? `Uploading... ${uploadProgress}%` : 'Upload Video'}
            </button>
        </div>
    );
};

export default VideoUpload;