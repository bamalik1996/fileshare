@extends('layouts.app')

@section('title', 'Smart File Organization - AirToShare | Organize Your Files')
@section('description', 'Organize your shared files with custom folders and smart categorization. Create, manage, and search through your files efficiently with AirToShare.')
@section('keywords', 'file organization, custom folders, file management, smart categorization, file search, AirToShare')

@section('content')
<style>
    /* Smart File Organization Styles */
    .smart-org-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
    }

    .smart-org-header {
        text-align: center;
        margin-bottom: 3rem;
        padding: 2rem 0;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .smart-org-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
        opacity: 0.3;
    }

    .smart-org-title {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 1rem;
        position: relative;
        z-index: 1;
    }

    .smart-org-subtitle {
        font-size: 1.25rem;
        opacity: 0.9;
        max-width: 600px;
        margin: 0 auto;
        position: relative;
        z-index: 1;
    }

    .control-panel {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
        border: 1px solid #e5e7eb;
    }

    .control-section {
        margin-bottom: 2rem;
    }

    .control-section:last-child {
        margin-bottom: 0;
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .section-title i {
        color: #667eea;
    }

    /* Folder Creation */
    .folder-creation {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .folder-input {
        flex: 1;
        min-width: 250px;
        padding: 0.875rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: #f9fafb;
    }

    .folder-input:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .create-folder-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 0.875rem 2rem;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1rem;
    }

    .create-folder-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }

    /* File Upload Section */
    .upload-zone {
        border: 3px dashed #d1d5db;
        border-radius: 16px;
        padding: 3rem 2rem;
        text-align: center;
        background: #f9fafb;
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .upload-zone:hover {
        border-color: #667eea;
        background: #f0f4ff;
        transform: translateY(-2px);
    }

    .upload-zone.dragover {
        border-color: #667eea;
        background: #e0e7ff;
        transform: scale(1.02);
    }

    .upload-icon {
        font-size: 4rem;
        color: #9ca3af;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }

    .upload-zone:hover .upload-icon {
        color: #667eea;
        transform: scale(1.1);
    }

    .upload-text {
        font-size: 1.5rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
    }

    .upload-subtext {
        color: #6b7280;
        font-size: 1rem;
    }

    /* Search Bar */
    .search-container {
        position: relative;
        max-width: 500px;
        margin: 0 auto;
    }

    .search-input {
        width: 100%;
        padding: 1rem 1rem 1rem 3rem;
        border: 2px solid #e5e7eb;
        border-radius: 50px;
        font-size: 1rem;
        background: white;
        transition: all 0.3s ease;
    }

    .search-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 1.1rem;
    }

    /* Main Content Area */
    .content-area {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
        min-height: 600px;
    }

    .content-header {
        display: flex;
        justify-content: between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .view-toggle {
        display: flex;
        background: #f3f4f6;
        border-radius: 12px;
        padding: 0.25rem;
    }

    .view-btn {
        padding: 0.5rem 1rem;
        border: none;
        background: transparent;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        color: #6b7280;
    }

    .view-btn.active {
        background: white;
        color: #667eea;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .sort-dropdown {
        padding: 0.5rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        background: white;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .sort-dropdown:focus {
        outline: none;
        border-color: #667eea;
    }

    /* Folders Grid */
    .folders-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }

    .folder-item {
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 16px;
        padding: 1.5rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .folder-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }

    .folder-item:hover {
        transform: translateY(-4px);
        box-shadow: 0 15px 35px rgba(102, 126, 234, 0.15);
        border-color: #667eea;
    }

    .folder-item:hover::before {
        transform: scaleX(1);
    }

    .folder-icon {
        font-size: 3rem;
        color: #667eea;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }

    .folder-item:hover .folder-icon {
        transform: scale(1.1);
        color: #764ba2;
    }

    .folder-name {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 0.5rem;
        font-size: 1.1rem;
    }

    .folder-count {
        color: #6b7280;
        font-size: 0.875rem;
    }

    .folder-actions {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .folder-item:hover .folder-actions {
        opacity: 1;
    }

    .action-btn {
        background: rgba(255, 255, 255, 0.9);
        border: none;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        cursor: pointer;
        margin-left: 0.25rem;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .action-btn:hover {
        background: white;
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .action-btn.delete:hover {
        background: #fee2e2;
        color: #dc2626;
    }

    .action-btn.edit:hover {
        background: #dbeafe;
        color: #2563eb;
    }

    /* Files Grid */
    .files-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 1rem;
    }

    .file-item {
        background: white;
        border: 2px solid #f3f4f6;
        border-radius: 12px;
        padding: 1rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .file-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border-color: #667eea;
    }

    .file-preview {
        width: 80px;
        height: 80px;
        margin: 0 auto 1rem;
        border-radius: 8px;
        overflow: hidden;
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .file-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .file-icon {
        font-size: 2rem;
        color: #9ca3af;
    }

    .file-name {
        font-weight: 500;
        color: #1f2937;
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .file-size {
        color: #6b7280;
        font-size: 0.75rem;
    }

    .file-checkbox {
        position: absolute;
        top: 0.5rem;
        left: 0.5rem;
        width: 20px;
        height: 20px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .file-item:hover .file-checkbox {
        opacity: 1;
    }

    .file-item.selected {
        border-color: #667eea;
        background: #f0f4ff;
    }

    .file-item.selected .file-checkbox {
        opacity: 1;
    }

    /* Navigation Breadcrumb */
    .breadcrumb-nav {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        padding: 1rem;
        background: #f9fafb;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
    }

    .breadcrumb-item {
        color: #6b7280;
        text-decoration: none;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .breadcrumb-item:hover {
        background: white;
        color: #667eea;
    }

    .breadcrumb-item.active {
        color: #1f2937;
        font-weight: 600;
        background: white;
    }

    .breadcrumb-separator {
        color: #d1d5db;
    }

    /* Action Bar */
    .action-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding: 1rem;
        background: #f9fafb;
        border-radius: 12px;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .selection-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .select-all-btn {
        background: #667eea;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
    }

    .select-all-btn:hover {
        background: #5a67d8;
        transform: translateY(-1px);
    }

    .selection-count {
        color: #6b7280;
        font-size: 0.875rem;
    }

    .bulk-actions {
        display: flex;
        gap: 0.5rem;
    }

    .bulk-btn {
        padding: 0.5rem 1rem;
        border: 2px solid #e5e7eb;
        background: white;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        color: #374151;
    }

    .bulk-btn:hover {
        border-color: #667eea;
        color: #667eea;
        transform: translateY(-1px);
    }

    .bulk-btn.danger:hover {
        border-color: #dc2626;
        color: #dc2626;
        background: #fef2f2;
    }

    /* Empty States */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #6b7280;
    }

    .empty-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .empty-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #374151;
    }

    .empty-text {
        font-size: 1rem;
        line-height: 1.6;
    }

    /* Quick Actions */
    .quick-actions {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }

    .quick-action {
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 1rem 1.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-weight: 500;
        color: #374151;
    }

    .quick-action:hover {
        border-color: #667eea;
        color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
    }

    .quick-action i {
        font-size: 1.25rem;
    }

    /* Statistics Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.5rem;
        text-align: center;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        color: #667eea;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        color: #6b7280;
        font-size: 0.875rem;
        font-weight: 500;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .smart-org-container {
            padding: 1rem;
        }

        .smart-org-title {
            font-size: 2rem;
        }

        .smart-org-subtitle {
            font-size: 1rem;
        }

        .folder-creation {
            flex-direction: column;
            align-items: stretch;
        }

        .folder-input {
            min-width: auto;
        }

        .content-header {
            flex-direction: column;
            align-items: stretch;
        }

        .action-bar {
            flex-direction: column;
            align-items: stretch;
        }

        .selection-info,
        .bulk-actions {
            justify-content: center;
        }

        .folders-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }

        .files-grid {
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        }

        .quick-actions {
            flex-direction: column;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    /* Animation Classes */
    .fade-in {
        animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .slide-in {
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from {
            transform: translateX(-20px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    /* Loading States */
    .loading-spinner {
        width: 20px;
        height: 20px;
        border: 2px solid #f3f4f6;
        border-top: 2px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Custom Scrollbar */
    .content-area::-webkit-scrollbar {
        width: 8px;
    }

    .content-area::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }

    .content-area::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .content-area::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>

<div class="smart-org-container">
    <!-- Header Section -->
    <div class="smart-org-header">
        <h1 class="smart-org-title">
            <i class="fas fa-folder-tree"></i>
            Smart File Organization
        </h1>
        <p class="smart-org-subtitle">
            Create custom folders, organize your attachments, and find files instantly with our intelligent file management system.
        </p>
    </div>

    <!-- Control Panel -->
    <div class="control-panel">
        <!-- Folder Creation Section -->
        <div class="control-section">
            <h3 class="section-title">
                <i class="fas fa-folder-plus"></i>
                Create New Folder
            </h3>
            <div class="folder-creation">
                <input type="text" class="folder-input" placeholder="Enter folder name (e.g., Documents, Images, Projects)" maxlength="50">
                <button class="create-folder-btn">
                    <i class="fas fa-plus"></i>
                    Create Folder
                </button>
            </div>
        </div>

        <!-- File Upload Section -->
        <div class="control-section">
            <h3 class="section-title">
                <i class="fas fa-cloud-upload-alt"></i>
                Upload Attachments
            </h3>
            <div class="upload-zone">
                <i class="fas fa-cloud-upload-alt upload-icon"></i>
                <div class="upload-text">Drag & Drop Files Here</div>
                <div class="upload-subtext">
                    or click to browse • Max 25MB per file • Images, Documents, Archives
                </div>
                <input type="file" style="display: none;" multiple accept="image/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,application/zip">
            </div>
        </div>

        <!-- Search Section -->
        <div class="control-section">
            <h3 class="section-title">
                <i class="fas fa-search"></i>
                Find Files
            </h3>
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Search files by name, type, or content...">
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <div class="quick-action">
            <i class="fas fa-images"></i>
            <span>View All Images</span>
        </div>
        <div class="quick-action">
            <i class="fas fa-file-pdf"></i>
            <span>View All Documents</span>
        </div>
        <div class="quick-action">
            <i class="fas fa-clock"></i>
            <span>Recent Files</span>
        </div>
        <div class="quick-action">
            <i class="fas fa-star"></i>
            <span>Favorites</span>
        </div>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">12</div>
            <div class="stat-label">Total Folders</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">48</div>
            <div class="stat-label">Total Files</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">156 MB</div>
            <div class="stat-label">Storage Used</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">5</div>
            <div class="stat-label">Shared Today</div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="content-area">
        <!-- Navigation Breadcrumb -->
        <div class="breadcrumb-nav">
            <a href="#" class="breadcrumb-item active">
                <i class="fas fa-home"></i>
                All Files
            </a>
            <i class="fas fa-chevron-right breadcrumb-separator"></i>
            <a href="#" class="breadcrumb-item">
                <i class="fas fa-folder"></i>
                Documents
            </a>
        </div>

        <!-- Content Header -->
        <div class="content-header">
            <div class="view-toggle">
                <button class="view-btn active">
                    <i class="fas fa-th"></i>
                </button>
                <button class="view-btn">
                    <i class="fas fa-list"></i>
                </button>
            </div>
            <select class="sort-dropdown">
                <option>Sort by Name</option>
                <option>Sort by Date</option>
                <option>Sort by Size</option>
                <option>Sort by Type</option>
            </select>
        </div>

        <!-- Action Bar -->
        <div class="action-bar">
            <div class="selection-info">
                <button class="select-all-btn">
                    <i class="fas fa-check-square"></i>
                    Select All
                </button>
                <span class="selection-count">0 items selected</span>
            </div>
            <div class="bulk-actions">
                <button class="bulk-btn">
                    <i class="fas fa-download"></i>
                    Download
                </button>
                <button class="bulk-btn">
                    <i class="fas fa-folder"></i>
                    Move to Folder
                </button>
                <button class="bulk-btn danger">
                    <i class="fas fa-trash"></i>
                    Delete
                </button>
            </div>
        </div>

        <!-- Folders Section -->
        <div class="folders-section">
            <h3 class="section-title">
                <i class="fas fa-folder"></i>
                Folders
            </h3>
            <div class="folders-grid">
                <!-- Sample Folders -->
                <div class="folder-item">
                    <div class="folder-actions">
                        <button class="action-btn edit" title="Rename">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="folder-icon">
                        <i class="fas fa-folder"></i>
                    </div>
                    <div class="folder-name">Documents</div>
                    <div class="folder-count">12 files</div>
                </div>

                <div class="folder-item">
                    <div class="folder-actions">
                        <button class="action-btn edit" title="Rename">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="folder-icon">
                        <i class="fas fa-folder"></i>
                    </div>
                    <div class="folder-name">Images</div>
                    <div class="folder-count">24 files</div>
                </div>

                <div class="folder-item">
                    <div class="folder-actions">
                        <button class="action-btn edit" title="Rename">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="folder-icon">
                        <i class="fas fa-folder"></i>
                    </div>
                    <div class="folder-name">Projects</div>
                    <div class="folder-count">8 files</div>
                </div>

                <div class="folder-item">
                    <div class="folder-actions">
                        <button class="action-btn edit" title="Rename">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="folder-icon">
                        <i class="fas fa-folder"></i>
                    </div>
                    <div class="folder-name">Archives</div>
                    <div class="folder-count">4 files</div>
                </div>
            </div>
        </div>

        <!-- Files Section -->
        <div class="files-section">
            <h3 class="section-title">
                <i class="fas fa-file"></i>
                Recent Files
            </h3>
            <div class="files-grid">
                <!-- Sample Files -->
                <div class="file-item">
                    <input type="checkbox" class="file-checkbox">
                    <div class="file-preview">
                        <img src="https://images.pexels.com/photos/1181671/pexels-photo-1181671.jpeg?auto=compress&cs=tinysrgb&w=200" alt="Sample Image">
                    </div>
                    <div class="file-name">vacation-photo.jpg</div>
                    <div class="file-size">2.4 MB</div>
                </div>

                <div class="file-item">
                    <input type="checkbox" class="file-checkbox">
                    <div class="file-preview">
                        <i class="fas fa-file-pdf file-icon" style="color: #dc2626;"></i>
                    </div>
                    <div class="file-name">report-2025.pdf</div>
                    <div class="file-size">1.8 MB</div>
                </div>

                <div class="file-item">
                    <input type="checkbox" class="file-checkbox">
                    <div class="file-preview">
                        <img src="https://images.pexels.com/photos/1181677/pexels-photo-1181677.jpeg?auto=compress&cs=tinysrgb&w=200" alt="Sample Image">
                    </div>
                    <div class="file-name">presentation.jpg</div>
                    <div class="file-size">3.1 MB</div>
                </div>

                <div class="file-item">
                    <input type="checkbox" class="file-checkbox">
                    <div class="file-preview">
                        <i class="fas fa-file-word file-icon" style="color: #2563eb;"></i>
                    </div>
                    <div class="file-name">meeting-notes.docx</div>
                    <div class="file-size">456 KB</div>
                </div>

                <div class="file-item">
                    <input type="checkbox" class="file-checkbox">
                    <div class="file-preview">
                        <i class="fas fa-file-archive file-icon" style="color: #7c3aed;"></i>
                    </div>
                    <div class="file-name">project-files.zip</div>
                    <div class="file-size">8.2 MB</div>
                </div>

                <div class="file-item">
                    <input type="checkbox" class="file-checkbox">
                    <div class="file-preview">
                        <img src="https://images.pexels.com/photos/1181263/pexels-photo-1181263.jpeg?auto=compress&cs=tinysrgb&w=200" alt="Sample Image">
                    </div>
                    <div class="file-name">screenshot.png</div>
                    <div class="file-size">892 KB</div>
                </div>

                <div class="file-item">
                    <input type="checkbox" class="file-checkbox">
                    <div class="file-preview">
                        <i class="fas fa-file-alt file-icon" style="color: #059669;"></i>
                    </div>
                    <div class="file-name">readme.txt</div>
                    <div class="file-size">12 KB</div>
                </div>

                <div class="file-item">
                    <input type="checkbox" class="file-checkbox">
                    <div class="file-preview">
                        <img src="https://images.pexels.com/photos/1181675/pexels-photo-1181675.jpeg?auto=compress&cs=tinysrgb&w=200" alt="Sample Image">
                    </div>
                    <div class="file-name">design-mockup.jpg</div>
                    <div class="file-size">4.7 MB</div>
                </div>
            </div>
        </div>

        <!-- Empty State (Hidden by default, shown when no files) -->
        <div class="empty-state" style="display: none;">
            <div class="empty-icon">
                <i class="fas fa-folder-open"></i>
            </div>
            <div class="empty-title">No Files Yet</div>
            <div class="empty-text">
                Start by creating folders and uploading your first files.<br>
                Drag and drop files into the upload zone above to get started.
            </div>
        </div>
    </div>
</div>
@endsection