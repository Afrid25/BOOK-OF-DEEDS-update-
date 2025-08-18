import React from 'react';
import VideoUpload from '../components/VideoUpload';
import TextNoteUpload from '../components/TextNoteUpload';

const Home: React.FC = () => {
    return (
        <div>
            <h1>Welcome to the Auto Video Summary and Slide Builder</h1>
            <p>Upload your educational videos or text notes to receive AI-generated summaries.</p>
            <VideoUpload />
            <TextNoteUpload />
        </div>
    );
};

export default Home;