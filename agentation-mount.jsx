import React from 'react';
import { createRoot } from 'react-dom/client';
import { Agentation } from 'agentation';

// Create a container for the Agentation component
const rootElement = document.createElement('div');
rootElement.id = 'agentation-root';
// Make sure it doesn't interfere with the layout
rootElement.style.position = 'fixed';
rootElement.style.zIndex = '999999';
document.body.appendChild(rootElement);

// Mount the component
const root = createRoot(rootElement);
root.render(<Agentation />);
