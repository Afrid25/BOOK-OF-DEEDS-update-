export interface VideoUploadProps {
    onUpload: (file: File) => void;
    uploadProgress: number;
}

export interface TextNoteUploadProps {
    onUpload: (file: File) => void;
    uploadProgress: number;
}

export interface Summary {
    id: string;
    content: string;
}

export interface Slide {
    title: string;
    points: string[];
}

export interface SlideBuilderProps {
    summary: Summary;
    onCreateSlides: (slides: Slide[]) => void;
}

export interface SlideSectionProps {
    slide: Slide;
}