@extends('layouts.app')

@section('title', 'Home Page')

@section('content')
<style>

        .step-icon {
            font-size: 48px;
            color: #00AEEF;
        }

        .step-box {
            background: white;
            border-radius: 12px;
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 30px;
        }

        .step-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
        }

        .step-description {
            font-size: 1rem;
            color: #666;
            line-height: 1.6;
        }

        .highlight {
            font-weight: bold;
            color: #ff6f00;
        }

        .highlight-center {
            font-weight: bold;
            color: #ff6f00;
            text-align: center;
            display: block;
            margin-top: 10px;
            font-size: 1.2rem;
        }

        .cta-button {
            background-color: #00AEEF;
            color: white;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 6px;
            text-align: center;
            margin-top: 20px;
            display: inline-block;
        }

        .cta-button:hover {
            background-color: #007BB5;
        }



    </style>
</style>
        <h1 class="title is-2 has-text-centered has-text-primary">How It Works</h1>
        
        <div class="columns is-centered">
            <!-- Step 1: Connect to Same Wi-Fi -->
            <div class="column is-one-third">
                <div class="step-box">
                    <div class="has-text-centered">
                        <span class="step-icon">📶</span>
                    </div>
                    <h3 class="step-title has-text-centered">Step 1: Connect to Same Wi-Fi</h3>
                    <p class="step-description has-text-centered">Make sure all the devices you want to share files or text with are connected to the same Wi-Fi network. It’s that easy!</p>
                </div>
            </div>

            <!-- Step 2: Upload to AirForShare -->
            <div class="column is-one-third">
                <div class="step-box">
                    <div class="has-text-centered">
                        <span class="step-icon">📂</span>
                    </div>
                    <h3 class="step-title has-text-centered">Step 2: Upload Anything</h3>
                    <p class="step-description has-text-centered">Upload files, text, or links to AirForShare from any of your connected devices. Share instantly with others on the same network.</p>
                </div>
            </div>

            <!-- Step 3: View and Manage Content -->
            <div class="column is-one-third">
                <div class="step-box">
                    <div class="has-text-centered">
                        <span class="step-icon">🔄</span>
                    </div>
                    <h3 class="step-title has-text-centered">Step 3: View & Manage</h3>
                    <p class="step-description has-text-centered">Once uploaded, access and manage your content from any device.</p>
                </div>
            </div>
        </div>
        <p class="highlight-center">*Content will be deleted after 30 minutes of last access*</p>
        <!-- CTA Button -->
        <div class="has-text-centered">
            <a href="/" class="cta-button">Get Started with AirForShare</a>
        </div>
    @endsection
