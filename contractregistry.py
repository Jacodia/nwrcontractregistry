import streamlit as st
import pandas as pd
from datetime import datetime, timedelta
import smtplib
import ssl
import os
import io

# --- APP CONFIGURATION AND STYLING ---
st.set_page_config(page_title="NWR Contract Registry", layout="wide")

st.markdown("""
<style>
    .stApp {
        background-color: #2c3e50; /* Dark blue-grey background */
        color: #ecf0f1; /* Off-white text */
        font-family: 'Inter', sans-serif;
    }
    h1.main-title {
        text-align: center;
        color: #ecf0f1; /* Off-white for main title */
        font-size: 3rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }
    .stTabs [data-baseweb="tab-list"] button {
        background-color: #3498db;
        color: white;
        border-radius: 8px;
        font-weight: bold;
        padding: 10px 20px;
        margin: 5px;
        transition: background-color 0.3s;
    }
    .stTabs [data-baseweb="tab-list"] button:hover {
        background-color: #2980b9;
    }
    .stButton>button {
        width: 100%;
        font-weight: bold;
        color: white;
        background-color: #3498db;
        border: none;
        border-radius: 8px;
        padding: 12px;
        transition: background-color 0.3s, transform 0.2s;
    }
    .stButton>button:hover {
        background-color: #2980b9;
        transform: translateY(-2px);
    }
    .stTextInput>div>div>input, .stTextArea>div>div>textarea, .stSelectbox>div>div>div>input {
        background-color: #34495e; /* Darker input fields */
        color: #ecf0f1;
        border: 1px solid #7f8c8d;
        border-radius: 8px;
    }
    .stTextInput>div>div>input::placeholder, .stTextArea>div>div>textarea::placeholder {
        color: #bdc3c7; /* Lighter placeholder text */
    }
    /* Style for the dataframe */
    .stDataFrame {
        color: #ecf0f1;
    }
    .notification-due-row {
        background-color: #e74c3c; /* Red color for urgent notification */
    }
</style>
""", unsafe_allow_html=True)

# --- DATA LOADING AND PROCESSING ---
# Hardcoding the contract data from the provided documents for a self-contained example
# In a real app, you would load this from a database or a file
if 'contracts_df' not in st.session_state:
    contracts_data = [
        {"Parties": "NWR // FNB", "Type of contract": "Credit OD Facility", "Duration": "One-year", "Expiry date": "2024-12-31", "Review by date": "2024-11-01", "Notification Date": "2024-11-01", "Contract value": "N$6,500,000.00"},
        {"Parties": "NWR // Alliance Media", "Type of contract": "Lease agreement", "Duration": "10 years", "Expiry date": "2025-12-31", "Review by date": "2025-10-01", "Notification Date": "2025-09-08", "Contract value": "N$86,940.00"},
        {"Parties": "NWR // NBC", "Type of contract": "Barter agreement", "Duration": "2 years", "Expiry date": "2024-10-31", "Review by date": "2024-09-01", "Notification Date": "2024-09-01", "Contract value": "N$78,499.00"},
        {"Parties": "NWR // MTC", "Type of contract": "MTC 3G/4G", "Duration": "24 months", "Expiry date": "Not specified", "Review by date": "Not specified", "Notification Date": "Not specified", "Contract value": "Not specified"},
        {"Parties": "NWR // Tungeni Investments", "Type of contract": "PPP – von Bach", "Duration": "50 years", "Expiry date": "2058-06-30", "Review by date": "2058-01-01", "Notification Date": "2058-01-01", "Contract value": "N$120,000.00"},
        {"Parties": "NWR // Powercom", "Type of contract": "Lease agreement for an internet tower", "Duration": "36 months", "Expiry date": "2027-10-31", "Review by date": "Not specified", "Notification Date": "2027-10-01", "Contract value": "Not specified"},
        {"Parties": "NWR // Ricoh", "Type of contract": "Printers rental", "Duration": "36 months", "Expiry date": "2026-03-31", "Review by date": "Not specified", "Notification Date": "2026-03-01", "Contract value": "Not specified"},
        {"Parties": "NWR // Microsoft", "Type of contract": "Volume licensing", "Duration": "36 months", "Expiry date": "2026-03-31", "Review by date": "Not specified", "Notification Date": "2026-03-01", "Contract value": "Not specified"},
        {"Parties": "NWR // BCX", "Type of contract": "Offsite backup of data", "Duration": "12 months", "Expiry date": "2026-08-01", "Review by date": "Not specified", "Notification Date": "2026-07-15", "Contract value": "Not specified"},
        {"Parties": "NWR // CIMSO", "Type of contract": "ERP system (Innkeeper)", "Duration": "12 months", "Expiry date": "2025-07-31", "Review by date": "Not specified", "Notification Date": "2025-07-01", "Contract value": "N$87,548.66"},
        {"Parties": "NWR // Microsoft", "Type of contract": "Office 365", "Duration": "12 months", "Expiry date": "2024-11-01", "Review by date": "Not specified", "Notification Date": "2024-10-15", "Contract value": "Not specified"},
    ]
    st.session_state.contracts_df = pd.DataFrame(contracts_data)

df = st.session_state.contracts_df
# Convert string dates to datetime objects for comparison
df['Notification Date'] = pd.to_datetime(df['Notification Date'], errors='coerce')

# --- EMAIL SENDING LOGIC ---
def send_email(receiver_email, subject, message_body):
    """
    Sends an email using a secure connection.

    NOTE: For a production app, never hardcode credentials.
    Use environment variables (e.g., os.environ) to store sensitive information.
    This function is a placeholder and requires a valid email setup to work.
    """
    try:
        smtp_server = "smtp.gmail.com"
        port = 587
        sender_email = "your_email@example.com"
        password = "your_email_password"

        message = f"Subject: {subject}\n\n{message_body}"
        context = ssl.create_default_context()

        with smtplib.SMTP(smtp_server, port) as server:
            server.starttls(context=context)
            server.login(sender_email, password)
            server.sendmail(sender_email, receiver_email, message)
        return True
    except Exception as e:
        st.error(f"Failed to send email. Error: {e}")
        return False

# --- STREAMLIT PAGE LAYOUT ---
st.markdown("<h1 class='main-title'>NWR Contract Registry</h1>", unsafe_allow_html=True)
st.markdown("<p style='text-align: center; color: #bdc3c7; font-size: 1.1rem;'>Dashboard for managing and monitoring company contracts.</p>", unsafe_allow_html=True)

# Tabs for different functionalities
tabs = st.tabs(["Dashboard", "Manage Contracts", "Send Notification"])

with tabs[0]:
    st.header("Contract Overview")

    # Filter by search box
    search_query = st.text_input("Search Contracts", placeholder="Search by Party or Contract Type...")
    if search_query:
        filtered_df = df[df.apply(lambda row: row.astype(str).str.contains(search_query, case=False).any(), axis=1)]
    else:
        filtered_df = df.copy()

    # Sort by Notification Date for better visibility
    filtered_df = filtered_df.sort_values(by='Notification Date', ascending=True)

    # Highlight contracts past their notification date
    today = datetime.now().date()
    # Create a style function
    def highlight_rows(row):
        notification_date_str = row['Notification Date']
        if pd.notna(notification_date_str):
            notification_date = notification_date_str.date()
            if notification_date <= today:
                return ['background-color: #e74c3c; color: white;'] * len(row)
        return [''] * len(row)

    st.markdown("---")
    st.info("Contracts with a notification date on or before today are highlighted in red.")
    st.dataframe(filtered_df.style.apply(highlight_rows, axis=1), use_container_width=True)

with tabs[1]:
    st.header("Add a New Contract")
    st.markdown("---")
    
    st.warning("The PDF upload feature is temporarily unavailable due to a missing library. Please add contracts manually using the form below.")

    with st.form("new_contract_form"):
        st.subheader("Contract Details")
        new_parties = st.text_input("Parties")
        new_type = st.text_input("Type of Contract")
        new_duration = st.text_input("Duration")
        new_expiry = st.text_input("Expiry Date", placeholder="YYYY-MM-DD")
        new_review = st.text_input("Review by Date", placeholder="YYYY-MM-DD")
        new_notification = st.text_input("Notification Date", placeholder="YYYY-MM-DD (e.g., 2025-09-08)")
        new_value = st.text_input("Contract Value")
        
        submitted = st.form_submit_button("Add Contract")
        if submitted:
            if new_parties and new_type:
                new_row = {
                    "Parties": new_parties,
                    "Type of contract": new_type,
                    "Duration": new_duration,
                    "Expiry date": new_expiry,
                    "Review by date": new_review,
                    "Notification Date": new_notification,
                    "Contract value": new_value
                }
                new_df = pd.DataFrame([new_row])
                # Append to the session state dataframe
                st.session_state.contracts_df = pd.concat([st.session_state.contracts_df, new_df], ignore_index=True)
                st.success("New contract added successfully!")
            else:
                st.error("Parties and Type of Contract are required.")

with tabs[2]:
    st.header("Send a Contract Notification")
    st.markdown("---")

    today = datetime.now().date()
    notification_due = df[pd.to_datetime(df['Notification Date'], errors='coerce').dt.date <= today]
    
    st.info("Select a contract below to send an email notification.")

    if not notification_due.empty:
        st.warning(f"There are {len(notification_due)} contracts past their notification date!")
        contract_options_due = notification_due['Parties'] + " - " + notification_due['Type of contract'] + " (DUE)"
        selected_contract_option = st.selectbox(
            "Select an Overdue Contract to Notify:",
            options=['-- Select a contract --'] + list(contract_options_due)
        )
    else:
        st.success("No contracts are currently past their notification date. Good job!")
        all_contract_options = df['Parties'] + " - " + df['Type of contract']
        selected_contract_option = st.selectbox(
            "Select a Contract to Notify:",
            options=['-- Select a contract --'] + list(all_contract_options)
        )

    if selected_contract_option != '-- Select a contract --':
        # Get the original index to find the row in the main dataframe
        if "(DUE)" in selected_contract_option:
            selected_row_index = notification_due[contract_options_due == selected_contract_option].index[0]
        else:
            selected_row_index = df[all_contract_options == selected_contract_option].index[0]
            
        selected_row = df.loc[selected_row_index]
        
        pre_filled_subject = f"Notification: {selected_row['Type of contract']} contract review"
        pre_filled_message = f"Hi,\n\nThis is a reminder that the contract with {selected_row['Parties']} for {selected_row['Type of contract']} is due for review on {selected_row['Notification Date'].strftime('%Y-%m-%d')}. Please take the necessary action.\n\nBest regards,\nNWR Registry"
        
        receiver_email = st.text_input("Recipient Email", placeholder="john.doe@example.com")
        subject = st.text_input("Subject", value=pre_filled_subject)
        message_body = st.text_area("Message", value=pre_filled_message, height=200)

        if st.button("Send Email"):
            if receiver_email and subject and message_body:
                with st.spinner('Sending email...'):
                    if send_email(receiver_email, subject, message_body):
                        st.success("Email sent successfully!")
                    else:
                        st.error("There was an error sending the email. Please check your email credentials and settings.")
            else:
                st.warning("Please fill in the recipient email, subject, and message body.")
