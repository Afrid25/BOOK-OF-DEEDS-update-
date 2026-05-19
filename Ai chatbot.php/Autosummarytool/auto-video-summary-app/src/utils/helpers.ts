export const formatText = (text: string): string => {
    return text.trim().replace(/\s+/g, ' ');
};

export const generateSlideContent = (summary: string): { title: string; points: string[] }[] => {
    const sections = summary.split('\n').filter(section => section.trim() !== '');
    return sections.map((section, index) => ({
        title: `Slide ${index + 1}`,
        points: section.split('.').map(point => point.trim()).filter(point => point !== '')
    }));
};

export const isValidFileType = (file: File, validTypes: string[]): boolean => {
    return validTypes.includes(file.type);
};