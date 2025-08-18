# Auto Video Summary and Slide Builder

## Overview
The Auto Video Summary and Slide Builder is a web application designed to help students efficiently summarize educational videos and text notes. By leveraging AI technology, users can upload their content and receive concise summaries, which can then be transformed into slide-like sections for easy review and presentation.

## Features
- **Video Upload**: Users can upload educational videos in MP4 format.
- **Text Note Upload**: Supports uploading of text notes in TXT, PDF, and DOCX formats.
- **AI-Generated Summaries**: Automatically generates summaries from uploaded videos and text notes.
- **Slide Builder**: Converts summaries into structured slide sections with titles and bullet points.
- **User-Friendly Interface**: Intuitive design for easy navigation and interaction.

## Project Structure
```
auto-video-summary-app
├── src
│   ├── components
│   │   ├── VideoUpload.tsx
│   │   ├── TextNoteUpload.tsx
│   │   ├── SummaryGenerator.tsx
│   │   ├── SlideBuilder.tsx
│   │   └── SlideSection.tsx
│   ├── pages
│   │   ├── Home.tsx
│   │   └── Summary.tsx
│   ├── services
│   │   ├── aiSummaryService.ts
│   │   └── slideService.ts
│   ├── utils
│   │   └── helpers.ts
│   ├── types
│   │   └── index.ts
│   └── App.tsx
├── public
│   └── index.html
├── package.json
├── tsconfig.json
└── README.md
```

## Installation
1. Clone the repository:
   ```
   git clone <repository-url>
   ```
2. Navigate to the project directory:
   ```
   cd auto-video-summary-app
   ```
3. Install the dependencies:
   ```
   npm install
   ```

## Usage
1. Start the development server:
   ```
   npm start
   ```
2. Open your browser and go to `http://localhost:3000` to access the application.
3. Follow the on-screen instructions to upload videos or text notes and generate summaries.

## Contributing
Contributions are welcome! Please feel free to submit a pull request or open an issue for any enhancements or bug fixes.

## License
This project is licensed under the MIT License. See the LICENSE file for more details.